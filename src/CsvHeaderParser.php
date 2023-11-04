<?php

namespace JeroenZwart\CsvSeeder;

use Illuminate\Support\Facades\DB;

/**
 * Class CsvHeaderParser.
 *
 * @package JeroenZwart\CsvSeeder
 */
class CsvHeaderParser
{
    protected $aliases      = [];
    protected $skipper      = '%';

    protected $table;
    protected $key;
    protected $name;

    protected $parsedHeader = [];

    /**
     * Set the tablename
     *
     * @param string $tablename
     */
    public function __construct( $connection, $tablename, $aliases, $skipper )
    {
        $this->table = DB::connection( $connection )->getSchemaBuilder()->getColumnListing( $tablename );

        $this->aliases = $aliases === NULL ? $this->aliases : $aliases;

        $this->skipper = $skipper === NULL ? $this->skipper : $skipper;
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

            $this->checkColumns();;
        }

        return $this->parsedHeader;
    }

    /**
     * Rename columns with aliassen
     *
     * @return void
     */
    protected function aliasColumns()
    {
        if( empty($this->aliases) ) return;

        if( array_key_exists($this->name, $this->aliases) )
        {
            $this->name = $this->aliases[$this->name];
            $this->parsedHeader[$this->key] = $this->name;
        }
    }

    /**
     * Skip columns starting with a given string, default %
     *
     * @return void
     */
    protected function skipColumns()
    {
        if( ! isset($this->skipper) ) return;

        if( $this->skipper != substr($this->name, 0, 1) ) $this->parsedHeader[$this->key] = $this->name;
    }

    /**
     * Check if a column exists in the table
     *
     * @return void
     */
    protected function checkColumns()
    {
        if( ! in_array($this->name, $this->table) ) unset($this->parsedHeader[$this->key]);
    }

}
