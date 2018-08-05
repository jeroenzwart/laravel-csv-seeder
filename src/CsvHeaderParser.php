<?php

namespace JeroenZwart\CsvSeeder;

use DB;

class CsvHeaderParser
{   
    protected $aliases      = [];
    protected $skipper      = '%';

    private $table;
    private $key;
    private $name;

    private $parsedHeader = [];

    /**
     * Set the tablename
     *
     * @param string $tablename
     */
    public function __construct( $tablename )
    {
        $this->table = DB::getSchemaBuilder()->getColumnListing( $tablename );
    }

    /**
     * Parse the given header for seeding
     *
     * @param array $header
     * @return array The parsed header
     */
    public function parseHeader( $header )
    {
        if( empty($this->table) or empty($header) ) return;

        foreach( $header as $this->key => $this->name )
        {
            $this->aliasColumns();

            $this->skipColumns();

            $this->checkColumns();
        }

        return $this->parsedHeader;
    }

    /**
     * Rename columns with aliassen
     *
     * @return void
     */
    private function aliasColumns()
    {
        if( empty($this->aliases) ) return;

        if( array_key_exists($this->name, $this->aliases) ) $this->parsedHeader[$this->key] = $this->aliases[$this->name];
    }

    /**
     * Skip columns starting with a given string, default %
     *
     * @return void
     */
    private function skipColumns()
    {
        if( ! isset($this->skipper) ) return; 

        if( $this->skipper != substr($this->name, 0, 1) ) $this->parsedHeader[$this->key] = $this->name;
    }
    
    /**
     * Check if a column exists in the table
     *
     * @return void
     */
    private function checkColumns()
    {
        if( ! in_array($this->name, $this->table) ) unset($this->parsedHeader[$this->key]);
    }

}