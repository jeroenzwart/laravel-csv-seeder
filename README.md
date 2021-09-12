![PHP from Packagist](https://img.shields.io/packagist/php-v/jeroenzwart/laravel-csv-seeder?style=flat-square)
[![Latest Version on Packagist][ico-version]][link-packagist]
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/jeroenzwart/laravel-csv-seeder?style=flat-square)
[![Total Downloads][ico-downloads]][link-downloads]
![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/jeroenzwart/laravel-csv-seeder?style=flat-square)


[ico-version]: https://img.shields.io/packagist/v/jeroenzwart/laravel-csv-seeder.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jeroenzwart/laravel-csv-seeder.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/jeroenzwart/laravel-csv-seeder
[link-downloads]: https://packagist.org/packages/jeroenzwart/laravel-csv-seeder

# Laravel CSV Seeder
> #### Seed your database using CSV files with Laravel or Lumen

With this package you can save time for seeding your database. Instead of typing out the seeder files, you can use CSV files to fill up the database of your project. There are configuration options available to control the insert the data of your CSV files.

### Features

- Automatically try to resolve CSV filename to table name.
- Automatic mapping of CSV headers to table column names.
- Skip seeding data with a prefix at in the CSV headers.
- Parse values with custom closure.
- Hash values with a given array of column names.
- Seed default values in to table columns.
- Adjust Laravel's timestamp at seeding.

## Installation
- Require this package directly by

    `composer require --dev jeroenzwart/laravel-csv-seeder`
    
- Or add this package in your composer.json and run `composer update`

    "jeroenzwart/laravel-csv-seeder": "1.*"

## Basic usage
Extend your seed classes with `JeroenZwart\CsvSeeder\CsvSeeder` and set the variable `$this->file` with the path of the CSV file. Tablename is not required, if the filename of the CSV is the same as the tablename. At last call `parent::run()` to seed. A seed class will look like this;
```php
use JeroenZwart\CsvSeeder\CsvSeeder;

class UsersTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Recommended when importing larger CSVs
        DB::disableQueryLog();
        parent::run();
    }
}
```
Place your CSV into the path */database/seeds/csvs/* of your Laravel project or whatever path you specify in the constructor. As default the given CSV require a header row with names, matching the columns names of the table in your database. Looks like this;

    first_name,last_name,birthday
    Foo,Bar,1970-01-01
    John,Doe,1980-01-01

## Configuration
- `tablename` *(string NULL)* - Name of table to insert data.
- `connection` *(string NULL)* - Name of database connection.
- `truncate` *(boolean TRUE)*  - Truncate the table before seeding.
- `foreignKeyCheck` *(boolean FALSE)*  - Enable or disable the foreign key checks.
- `header` *(boolean TRUE)* - CSV has a header row, set FALSE if not.
- `mapping` *(array [])* - Associative array of column names in order as CSV, if empty the first row of CSV will be used as header.
- `aliases` *(array [])* - Associative array of CSV header names and column names; csvColumnName => aliasColumnName.
- `skipper` *(string %)* - Skip a CSV header and data to import in the table.
- `validate` *(array [])* - Validate a CSV row with Laravel Validation.
- `parsers` *(array [])* - Associative array of column names to parse a value with the given closure.
- `hashable` *(array ['password'])* - Array of column names to hash their values. It uses Hash::make().
- `empty` *(boolean FALSE)* - Set TRUE for keeping an empty value in the CSV file to an empty string instead of a NULL.
- `defaults` *(array [])* - Array of table columns and it's values to seed, when they are empty in the CSV file.
- `timestamps` *(string|boolean TRUE)* - Set Laravel's timestamp in the database while seeding; set as TRUE will use current time.
- `delimiter` *(string ;)* - The used delimiter in the CSV files.
- `chunk` *(integer 50)* - Insert the data of rows every `chunk` while reading the CSV.
- `encode` *(boolean TRUE)* - Encode the value of rows to UTF-8

## Tip
Users of Microsoft Excel can use a macro to export there worksheets to CSV. Easiest is to name your worksheets as table name. Use the following macro to export;

    Public Sub SaveWorksheetsAsCsv()
    ActiveWorkbook.Save
    Dim xWs As Worksheet
    Dim xDir As String
    Dim folder As FileDialog
    Set folder = Application.FileDialog(msoFileDialogFolderPicker)
    If folder.Show <> -1 Then Exit Sub
    xDir = folder.SelectedItems(1)
    For Each xWs In Application.ActiveWorkbook.Worksheets
    xWs.SaveAs xDir & "\" & xWs.Name, xlCSV
    Next
    End Sub

## Examples
#### Table with given timestamps
Give the seeder a specific table name instead of using the CSV filename;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->tablename = 'email_users';
        $this->timestamps = '1970-01-01 00:00:00';
    }
```

#### Connection
Give the seeder a specific connection and table name.;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/other_users.csv';
        $this->connection = 'second_db';
        $this->tablename = 'second_users';
    }
```

#### Mapping
Map the CSV headers to table columns, with the following CSV;

    1,Foo,Bar
    2,John,Doe

Handle like this;    
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->mapping = ['id', 'firstname', 'lastname'];
        $this->header = FALSE;
    }
```

#### Aliases with defaults
Seed a table with aliases and default values, like this;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->aliases = ['csvColumnName' => 'table_column_name', 'foo' => 'bar'];
        $this->defaults = ['created_by' => 'seeder', 'updated_by' => 'seeder'];
    }
```

#### Skipper
Skip a column in a CSV with a prefix. For example you use `id` in your CSV and only usable in your CSV editor. The following CSV file looks like so;

    %id,first_name,last_name,%id_copy,birthday
    1,Foo,Bar,1,1970-01-01
    2,John,Doe,2,1980-01-01

The first and fourth value of each row will be skipped with seeding. The default prefix is '%' and changeable to;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->skipper = 'custom_';
    }
```

#### Validate
Validate each row of a CSV like this;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->validate = [ 'name'              => 'required',
                            'email'             => 'email',
                            'email_verified_at' => 'date_format:Y-m-d H:i:s',
                            'password'          => ['required', Rule::notIn([' '])]];
    }
```

#### Parse
Parse values of certain column when seeding a CSV like this;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->parsers = ['email' => function ($value) { 
            return strtolower($value);
        }];
    }
```

#### Hash
Hash values when seeding a CSV like this;
```php
    public function __construct()
    {
        $this->file = '/database/seeds/csvs/users.csv';
        $this->hashable = ['password', 'salt'];
    }
```

## License

Laravel CSV Seeder is open-sourced software licensed under the MIT license. Please see the [license file](license.md) for more information

## Donation

If this project helped you to reduce some time to develop, you can donate me a beer :)  
By Bitcoin 17jnh8oBkgLpXo3d9Xmq6i6hhYgooaYiGf or the link below;

[![paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4KJ5KBX9CLUUA&source=url)
