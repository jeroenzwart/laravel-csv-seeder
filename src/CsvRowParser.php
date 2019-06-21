<?php

namespace JeroenZwart\CsvSeeder;

use Validator;
use Hash;

class CsvRowParser
{
    private $header;
    private $defaults     = [];
    private $timestamps   = TRUE;
    private $hashable     = ['password'];
    private $validate     = [];

    private $key;
    private $name;
    private $value;
    private $row;
    private $parsedRow;
    
    /**
     * Set the header and possible options to add or parse a row
     *
     * @param array $header
     * @param array $defaults
     * @param string $timestamps
     * @param array $hashable
     * @param array $validate
     */
    public function __construct( $header, $defaults, $timestamps, $hashable, $validate )
    {
        $this->header = $header;

        $this->defaults = $defaults === NULL ? $this->defaults : $defaults;

        $this->timestamps = $timestamps === NULL ? $this->timestamps : $timestamps;

        $this->hashable = $hashable === NULL ? $this->hashable : $hashable;

        $this->validate = $validate === NULL ? $this->validate : $validate;
    }

    /**
     * Parse a CSV row to a database row
     *
     * @param array $row
     * @return array Returns the parsed row
     */
    public function parseRow( $row )
    {
        $this->row = $row;

        $this->mergeRowAndHeader();
        
        if( empty($this->header) or empty($this->row) ) return FALSE;

        $this->init();

        if( ! $this->doValidate() ) return FALSE;

        foreach( $this->row as $this->key => $this->value )
        {    
            $this->isEmptyValue();
            
            if(mb_detect_encoding($this->value) != 'UTF-8') {
                $this->doEncode();    
            }

            $this->doHashable();

            $this->parsedRow[ $this->key ] = $this->value;
        }

        $this->addDefaults();
        
        $this->addTimestamps();

        return $this->parsedRow;
    }

    /**
     * Merge/replace row keys and header values
     * 
     * @return void
     */
    private function mergeRowAndHeader( )
    {
        foreach( $this->header as $key => $value )
        {
            $merged[ $this->header[$key] ] = $this->row[$key];
        }

        if( isset($merged) ) $this->row = $merged;
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
     * Validate the row
     * 
     * @return void
     */
    private function doValidate()
    {
        if( empty($this->validate) ) return TRUE;

        $validator = Validator::make($this->row, $this->validate);

        if( $validator->fails() ) return FALSE;

        return TRUE;
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

        if( ! in_array($this->key, $this->hashable) ) return;

        $this->value = Hash::make( $this->value );
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
