<?php

namespace JeroenZwart\CsvSeeder;

use DB;
use Illuminate\Database\Seeder;
use JeroenZwart\CsvSeeder\CsvHeaderParser as CsvHeaderParser;
use JeroenZwart\CsvSeeder\CsvRowParser as CsvRowParser;

class CsvSeeder extends Seeder
{
    protected $file;
    protected $tablename;
    protected $truncate     = TRUE;
    protected $header       = TRUE;
    protected $mapping      = [];
    protected $hashable     = ['password'];
    protected $delimiter    = ';';
    protected $chunk        = 50;
    
    private $filepath;
    private $csvData;
    private $parsedData;
    private $count          = 0;
    private $total          = 0;
    private $offset         = 0;
    
    /**
     * Run the class
     *
     * @return void
     */
    public function run()
    {
        if( ! $this->checkFile() ) return;
        
        if( ! $this->checkFilepath() ) return;

        if( ! $this->checkTablename() ) return;
        
        $this->seeding();
    }

    /**
     * Require a CSV file
     *
     * @return boolean
     */
    private function checkFile()
    {
        if( $this->file ) return TRUE;

        $this->console( 'No CSV file given', 'error' );

        return FALSE;
    }

    /**
     * Check if the file is accesable
     *
     * @return boolean
     */
    private function checkFilepath()
    {
        $this->filepath = base_path() . $this->file;

        if( file_exists( $this->filepath ) || is_readable( $this->filepath ) ) return TRUE;

        $this->console( 'File "'.$this->file.'" could not be found or is readable', 'error' );

        return FALSE;
    }

    /**
     * Get the tablename by CSV filename and check if it exists in database
     *
     * @return boolean
     */
    private function checkTablename()
    {
        if( ! isset($this->tablename) ) 
        {
            $pathinfo = pathinfo( $this->filepath );

            $this->tablename = $pathinfo['filename'];
        }

        if( DB::getSchemaBuilder()->hasTable( $this->tablename ) ) return TRUE;

        $this->console( 'Table "'.$this->tablenamee.'" could not be found in database', 'error' );        

        return FALSE;
    }

    /**
     * Start with seeding the rows of the CSV
     *
     * @return void
     */
    private function seeding()
    {
        $this->truncateTable();

        $this->setTotal();

        $this->openCSV();

        $this->setHeader();

        $this->parseHeader();

        $this->parseCSV();

        $this->closeCSV();

        $this->outputParsed();
    }

    /**
     * Truncate the table
     *
     * @param boolean $foreignKeys
     * @return void
     */
    private function truncateTable( $foreignKeys = TRUE )
    {        
        if( ! $this->truncate ) return;

        if( ! $foreignKeys ) DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
       
        DB::table( $this->tablename )->truncate();
        
        if( ! $foreignKeys ) DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Set the total of CSV rows
     *
     * @return void
     */
    private function setTotal()
    {
        $file = file( $this->filepath, FILE_SKIP_EMPTY_LINES );

        $this->total = count( $file );

        if( $this->header == TRUE )  $this->total --;
    }

    /**
     * Open the CSV file
     *
     * @return void
     */
    private function openCSV()
    {
        if( $this->isGzipped( $this->filepath ) )
        {
            $data = gzopen( $this->filepath, 'r' );
        } 
        else
        {
            $data = fopen( $this->filepath, 'r' );
        }

        $this->csvData = $data;
    }

    /**
     * Set the header of the CSV file
     *
     * @return void
     */
    private function setHeader()
    {
        if( ! empty($this->mapping) ) return $this->header = $this->mapping;

        $this->header = $this->stripUtf8Bom( fgetcsv( $this->csvData, 0, $this->delimiter ) );

        $this->offset += 1;
    }

    /**
     * Parse the header of CSV to required columns
     *
     * @return void
     */
    private function parseHeader()
    {
        if( ! $this->header ) return;

        $parser = new CsvHeaderParser( $this->tablename );

        $this->header = $parser->parseHeader( $this->header );
    }

    /**
     * Parse each row of the CSV
     *
     * @return void
     */
    private function parseCSV()
    {
        if( ! $this->csvData || empty($this->header) ) return;

        $parser = new CsvRowParser( $this->header, $this->defaults, $this->timestamps, $this->hashable );

        while( ($row = fgetcsv( $this->csvData, 0, $this->delimiter )) !== FALSE )
        {
            $this->offset --;

            if( $this->offset > 0 ) continue;

            if( empty($row) ) continue;
                    
            $parsed = $parser->parseRow( $row );

            if( $parsed ) $this->parsedData[] = $parsed;

            $this->count ++;

            if( $this->count >= $this->chunk ) $this->insertRows();
        }

        $this->insertRows();
    }

    /**
     * Insert a chunk of rows in the table
     *
     * @return void
     */
    private function insertRows()
    {
        try 
        {
            DB::table( $this->tablename )->insert( $this->parsedData );

            $this->parsedData = [];

            $this->chunk ++;
        }
        catch (\Exception $e)
        {
            $this->console('Rows of the file "'.$this->file.'" has been failed to insert: ' . $e->getMessage(), 'error' );

            $this->closeCSV();

            die();
        }        
    }

    /**
     * Close the CSV file
     *
     * @return void
     */
    private function closeCSV()
    {
        if( ! $this->csvData ) return;
        
        fclose( $this->csvData );
    }

    /**
     * Output the result of seeding
     *
     * @return void
     */
    private function outputParsed()
    {
        $this->console( $this->count.' of '.$this->total.' rows has been seeded in table "'.$this->tablename.'"' );
    }
 
    /**
     * Check if file is gzipped
     *
     * @param string $file
     * @return boolean
     */
    private function isGzipped( $file )
    {   
        $file_info      = finfo_open( FILEINFO_MIME_TYPE );
        $file_mime_type = finfo_file( $file_info, $file );
        finfo_close($file_info);

        if( strcmp($file_mime_type, "application/x-gzip") == 0 ) return TRUE;

        return FALSE;
    } 

    /**
     * Strip 
     *
     * @param [type] $string
     * @return string
     */
    private function stripUtf8Bom( $string )
    {
        $bom    = pack('H*', 'EFBBBF');
        $string = preg_replace("/^$bom/", '', $string);
        
        return $string;
    }
    
    /**
     * Logging
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    private function console( $message, $level = FALSE )
    {
        if( $level ) $message = '<'.$level.'>'.$message.'</'.$level.'>';

        $this->command->line( '<comment>CsvSeeder: </comment>'.$message );
    }

}