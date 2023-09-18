<?php

namespace JeroenZwart\CsvSeeder;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

/**
 * Class CsvSeeder.
 *
 * @package JeroenZwart\CsvSeeder
 */
class CsvSeeder extends Seeder
{
    /**
     * Database Connection, if it's not the default
     *
     * @var string
     */
    public $connection;

    /**
     * Path of the CSV file
     *
     * @var string
     */
    public $file;

    /**
     * Table name of database, if not set uses filename of CSV
     *
     * @var string
     */
    public $tablename;

    /**
     * Truncate table before seeding
     * Default: TRUE
     *
     * @var boolean
     */
    public $truncate = TRUE;

    /**
     * Enable or disable the foreign key check
     * Default: FALSE
     *
     * @var boolean
     */
    public $foreignKeyCheck = FALSE;

    /**
     * If the CSV has headers, set TRUE
     * Default: TRUE
     *
     * @var boolean | array
     */
    public $header = TRUE;

    /**
     * The character that split the values in the CSV
     * Default: ';'
     *
     * @var string
     */
    public $delimiter = ';';

    /**
     * Array of column names used in the CSV
     * Name map the columns of the CSV to the columns in table
     * Mapping can also be used when there are headers in the CSV. The headers will be skipped.
     * Example: ['firstCsvColumn', 'secondCsvColumn']
     *
     * @var array
     */
    public $mapping;

    /**
     * Array of columns names as value with header name as index
     * Example: ['csvColumn' => 'tableColumn', 'foo' => 'bar']
     *
     * @var array
     */
    public $aliases;

    /**
     * Array of parsers as values with header name as index
     * Example: ['name' => function ($value) { return ucfirst($value); } ]
     *
     * @var array
     */
    public $parsers;

    /**
     * Array of column names to be hashed before inserting
     * Default: ['password']
     * Example: ['password', 'salt']
     *
     * @var array
     */
    public $hashable;

    /**
     * Array with Laravel Validation rules
     * Example: ['name' => 'required']
     *
     * @var array
     */
    public $validate;

    /**
     * Keep an empty value in the CSV to empty string instead of null
     * Default: FALSE
     *
     * @var boolean
     */
    public $empty = FALSE;

    /**
     * Array with default value for column(s) in the table
     * When value of a column is empty or no column in the CSV file, it is using the default value
     * Example: ['created_by' => 'seed', 'updated_by' => 'seed]
     *
     * @var array
     */
    public $defaults;

    /**
     * String of prefix used in CSV header, mapping or alias
     * When a CSV column name begins with the string, this column will be skipped to insert
     * Default: '%'
     * Example: CSV header '#id_copy' will be skipped with skipper set as '#'
     *
     * @var string
     */
    public $skipper;

    /**
     * Set the Laravel timestamps while seeding data
     * With TRUE the columns 'created_at' and 'updated_at' will be set with current date/time.
     * When set on FALSE, the fields will have NULL
     * Default: TRUE
     * Example: '1970-01-01 00:00:00'
     *
     * @var string
     */
    public $timestamps;

    /**
     * Number of rows to skip at the start of the CSV, excluding the header
     * Default: 0
     *
     * @var integer
     */
    public $offset = 0;

    /**
     * Insert into SQL database in blocks of CSV data while parsing the CSV file
     * Default: 50
     *
     * @var integer
     */
    public $chunk = 50;

    /**
     * Encode value of rows to UTF-8, set this to TRUE
     * Default: TRUE
     *
     * @var boolean
    */
    public $encode = TRUE;


    private $filepath;
    private $csvData;
    private $parsedData;
    private $count = 0;
    private $total = 0;

    /**
     * Run the class
     *
     * @return void
     */
    public function run()
    {
        $this->setConnection();

        if( ! $this->checkFile() ) return;

        if( ! $this->checkFilepath() ) return;

        if( ! $this->checkTablename() ) return;

        $this->seeding();
    }

    /**
     * Set the connection from config, if it is not already set.
     *
     * @return void
     */
    private function setConnection()
    {
        if ($this->connection !== NULL) return;

        $this->connection = config('database.default');

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
     * Check if the file is accessible
     *
     * @return boolean
     */
    private function checkFilepath()
    {
        $this->filepath = $this->file;

        if( file_exists( $this->filepath ) || is_readable( $this->filepath ) ) return TRUE;

        $this->filepath = base_path() . $this->file;

        if( file_exists( $this->filepath ) || is_readable( $this->filepath ) ) return TRUE;

        $this->console( 'File "'.$this->file.'" could not be found or is not readable', 'error' );

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

        if( DB::connection($this->connection)->getSchemaBuilder()->hasTable( $this->tablename ) ) return TRUE;

        $this->console( 'Table "'.$this->tablename.'" could not be found in database', 'error' );

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

        $this->setMapping();

        $this->parseHeader();

        $this->parseCSV();

        $this->closeCSV();

        $this->outputParsed();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    private function truncateTable()
    {
        if( $this->truncate === FALSE ) return;

        $this->toggleForeignKeyCheck(false);

        DB::connection($this->connection)->table( $this->tablename )->truncate();

        $this->toggleForeignKeyCheck(true);
    }

    /**
     * Toggle the foreign key check in certain database drivers.
     *
     * @param boolean $mode
     */
    private function toggleForeignKeyCheck( $mode )
    {
        if( $this->foreignKeyCheck === FALSE ) return;

        switch(config('database.connections.' . $this->connection . '.driver'))
        {
            case 'mysql':
                DB::connection($this->connection)->statement('SET FOREIGN_KEY_CHECKS = ' . (int) $mode . ';');
                break;
            case 'sqlite':
                DB::connection($this->connection)->statement('PRAGMA foreign_keys = ' . ($mode ? 'ON' : 'OFF') . ';');
                break;
        }
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
        $this->csvData = fopen( $this->filepath, 'r' );
    }

    /**
     * Set the header of the CSV file
     *
     * @return void
     */
    private function setHeader()
    {
        if( $this->header == FALSE ) return;

        $this->offset += 1;

        $this->header = $this->stripUtf8Bom( fgetcsv( $this->csvData, 0, $this->delimiter ) );
        if( count($this->header) == 1 ) $this->console( 'Found only one column in header, maybe a wrong delimiter ('.$this->delimiter.') for the CSV file was set' );
    }

    /**
     * Set mapping to headers variable
     *
     * @return void
     */
    private function setMapping()
    {
        if( empty($this->mapping) ) return;

        $this->header = $this->mapping;
    }

    /**
     * Parse the header of CSV to required columns
     *
     * @return void
     */
    private function parseHeader()
    {
        if( empty($this->header) ) return $this->console( 'No CSV headers were parsed' );

        $parser = new CsvHeaderParser( $this->connection, $this->tablename, $this->aliases, $this->skipper);

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

        $parser = new CsvRowParser( $this->header, $this->empty, $this->defaults, $this->timestamps, $this->parsers, $this->hashable, $this->validate, $this->encode );

        while( ($row = fgetcsv( $this->csvData, 0, $this->delimiter )) !== FALSE )
        {
            $this->offset --;

            if( $this->offset > 0 ) continue;

            if( empty($row) ) continue;

            $parsed = $parser->parseRow( $row );

            if( ! $parsed ) continue;

            $this->parsedData[] = $parsed;

            $this->count ++;

            if( count($this->parsedData) >= $this->chunk ) $this->insertRows();
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
        if( empty($this->parsedData) ) return;

        try
        {
            DB::connection($this->connection)->table( $this->tablename )->insert( $this->parsedData );

            $this->parsedData = [];
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
        if (isset($this->command) === FALSE) return;

        if( $level ) $message = '<'.$level.'>'.$message.'</'.$level.'>';

        $this->command->line( '<comment>CsvSeeder: </comment>'.$message );
    }

}
