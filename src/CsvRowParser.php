<?php

namespace JeroenZwart\CsvSeeder;

use Hash;

class CsvRowParser
{
    
    private $header;
    private $defaults     = [];
    private $timestamps   = TRUE;
    private $hashable     = ['password'];

    private $key;
    private $name;
    private $value;

    private $parsedRow;
    
    /**
     * Set the header and possible options to add or parse a row
     *
     * @param array $header
     * @param array $defaults
     * @param string $timestamps
     * @param array $hashable
     */
    public function __construct( $header, $defaults = FALSE, $timestamps = FALSE, $hashable = FALSE )
    {
        $this->header = $header;
        
        $this->defaults = $defaults ? $defaults : $this->defaults;

        $this->timestamps = $timestamps ? $timestamps : $this->timestamps;
        
        $this->hashable = $hashable ? $hashable : $this->hashable;
    }

    /**
     * Parse a CSV row to a database row
     *
     * @param array $row
     * @return array Returns the parsed row
     */
    public function parseRow( $row )
    {
        if( empty($this->header) or empty($row) or !array_filter($row) ) return FALSE;

        $this->init();

        foreach( $this->header as $this->key => $this->name )
        {
            if( ! array_key_exists($this->key, $row) ) continue;
            
            $this->value = $row[ $this->key ];
            
            $this->isEmptyValue();
            
            $this->doEncode();

            $this->doHashable();

            $this->addParsed();

            $this->addDefaults();
    
            $this->addTimestamps();
        }

        return $this->parsedRow;
    }

    /**
     * Clear the parsed row
     * 
     * @return void
     */
    private function init()
    {
        $this->parsedRow = [];
    }

    /**
     * Set the string value of a boolean to real boolean
     *
     * @return void
     */
    private function isEmptyValue()
    {
        if( strtoupper($this->value) == 'NULL' ) $this->value = NULL;

        if( strtoupper($this->value) == 'FALSE' ) $this->value = FALSE;

        if( strtoupper($this->value) == 'TRUE' ) $this->value = TRUE;
    }

    /**
     * Encode the value to UTF8
     * 
     * @return void
     */
    private function doEncode()
    {
        if( is_string($this->value) ) $this->value = utf8_encode( $this->value );
    }
   
    /**
     * Hash the value of given column(s), default: password
     * 
     * @return void
     */
    private function doHashable()
    {
        if( empty($this->hashable) ) return;

        if( ! in_array($this->name, $this->hashable) ) return;

        $this->value = Hash::make( $this->value );
    }

    /**
     * Add the parsed value to the parsed row
     * 
     * @return void
     */
    private function addParsed()
    {
        $this->parsedRow[ $this->name ] = $this->value;
    }

    /**
     * Add a default column with value to parsed row
     * 
     * @return void
     */
    private function addDefaults()
    {
        if( empty($this->defaults) ) return;

        foreach( $this->defaults as $key => $value )
        {
            $this->parsedRow[ $key ] = $value;
        }
    }
    
    /**
     * Add timestamp to the parsed row
     * 
     * @return void
     */
    private function addTimestamps()
    {
        if( empty($this->timestamps) ) return;

        if( $this->timestamps === TRUE ) $this->timestamps = date('Y-m-d H:i:s');

        $this->parsedRow[ 'created_at' ] = $this->timestamps;
        $this->parsedRow[ 'updated_at' ] = $this->timestamps;
    }   

}