# Laravel CSV Seeder
> #### Seed your database using CSV files with Laravel

With this package you can save time for seeding your database. Instead of typing out the seeder files, you can use CSV files to fill up the database of your project. There are configuration options available to control the insert the data of your CSV files.

### Features

- Automatically try to resolve CSV filename to table name.
- Automatic mapping of CSV headers to table column names.
- Skip seeding data with a prefix at in the CSV headers.
- Hash values with a given array of column names.
- Seed default values in to table columns.
- Adjust Laravel's timestamp at seeding.

## Installation
- Require this package directly by `composer require jeroenzwart/laravel-csv-seeder`
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
- `tablename` *(string*) - Name of table to insert data.
- `truncate` *(boolean TRUE)*  - Truncate the table before seeding.
- `header` *(boolean TRUE)* - CSV has a header row, set FALSE if not.
- `mapping` *(array [])* - Associative array of column names in order as CSV, if empy the first row of CSV will be used as header.
- `aliases` *(array [])* - Associative array of CSV header names and column names; csvColumnName => aliasColumnName.
- `skipper` *(string %)* - Skip a CSV header and data to import in the table
- `hashable` *(array ['password'])* - Array of column names to hash there values. It uses Hash::make().
- `defaults` *(array [])* - Array of table columns and its values to seed with CSV file.
- `timestamps` *(string/boolean TRUE)* - Set Laravel's timestamp in the database while seeding; set as TRUE will use current time.
- `delimiter` *(string ;)* - The used delimiter in the CSV files.
- `chunk` *(integer 50)* - Insert the data of rows every `chunk` while reading the CSV.

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
		$this->table = 'email_users';
		$this->timestamps = '1970-01-01 00:00:00';
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
		$this->headers = FALSE;
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
LaravelCsvSeeder is open-sourced software licensed under the MIT license.
