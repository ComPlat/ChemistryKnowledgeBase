# README

The formatting library `diqa/formatter` eases formatted text output in multiple columns on the console.

Features:
- Fixed column sizes
- Different alignments inside the columns
- Separators (horizontal lines)
- Border support
- Padding
- Color highlighting (for console)
- Word wrapping or shortening
- Char sequences can be ignored by layout mechanism 

## Quick start

First, you have to initialize the formatter with a configuration.

The only required option for the config object is the first parameter: **column width**. All
others are optional. In the given example we have a 3-column layout with
left alignment for the first column, center alignment for the second and right alignment
for the 3rd. We also opted-in borderPadding.

```
$config = new Config([20,30,20],
 [Config::LEFT_ALIGN, Config::CENTER_ALIGN, Config::RIGHT_ALIGN],
 [
  'borderPadding' => true 
 ]
);

$formatter = new Formatter($config);
```

Now you can use it. You either print one row or an array of rows.[1]

One row:
```
$output = $formatter->formatLine("column1", "column2", "column3");
```

Multiple rows:
```
$output = $formatter->format([
 ["column1", "column2", "column3"],   // row 1
 ["column1", "column2", "column3"],   // row 2
 ["column1", "column2", "column3"]    // row 3
]);
```

## Options
The config class has the following constructor parameters:

- array of **column widths** in characters, e.g. for 3 columns: `[ 20, 30 ,20 ]`

- array of **alignments** (optional, if missing LEFT_ALIGN is default for all columns) 
  - `Config::LEFT_ALIGN`
  - `Config::RIGHT_ALIGN`
  - `Config::CENTER_ALIGN`
  - `Config::LEFT_AND_RIGHT_ALIGN` 


- array of options. The following are currently available:
  - **borderPadding**: Adds 1-char-padding at the borders (left and right). Default is none. 
  - **border**: Adds a border [2]. Default is none.
  - **paddingChar**: Changes the padding character. default is a whitespace.
  - **wrapColumns**: Specifies if lines should be wrapped or shortened. Default is wrapped.
  

- Separator lines: A line can be one of the constants (holds for all columns)
  - `Config::LINE_SEPARATOR`: Renders a single line 
  - `Config::DOUBLE_LINE_SEPARATOR`: Renders a double line
  - `Config::EMPTY_LINE_SEPARATOR`: Renders an empty line

  ```
  $output = $formatter->format([
   ["column1", "column2", "column3"],   // row 1
   [Config::DOUBLE_LINE_SEPARATOR],     // row 2
   ["column1", "column2", "column3"]    // row 3
  ]);
  ```

Additionally, the following methods are available:
- `highlightWord ($word, $color, $column = NULL)`
  - Highlights a word with a color. The last parameter is optional. If missing, the word is highlighted in all columns. See this example:
    
    ```
    $warningColor = Color::fromColor(Color::LIGHT_GREY, Color::RED);
    $config->highlightWord("[ERROR]", $warningColor, 3);
    ```
    Highlights the string "[ERROR]" in 3rd column with red background and lightgrey text color.


- `setSequencesToIgnore (array $sequences)`
  - Specifies character sequences which should be completely ignored by layouting mechanism. This is useful when you want to output formatting data for a printer for example. See this example:
    
    ```
    $config->setSequencesToIgnore(["//BOLD", "//ITALIC", "//OFF"]);
    ```
    These strings are completely ignored by the layout, for example "//BOLDHaus//OFF" is regarded as if it would be "Haus" only




- `setLeftColumnPadding(int $column, int $leftPadding)`
  - Specifies how much left-padding is applied on $column

# Alignments
While left, center and right alignments are quite self-explanatory, left-right alignment might not be.
It means that a single column consists of two parts. First part is aligned 
left, second right. 
Example:
```
Date of purchase..................2020-07-12
Shop.......................Aldi Süd Oststadt
Product..Tütensuppe Miraculi Familienpackung
```
You cannot use a two column layout for this because in 1st row the first column
overlaps the second column in the 3rd row.

Columns consisting of two parts are entered as arrays with two entries. So be 
careful: There are 3 arrays nesting. first is the rows, second the columns (actually only one here)
and 3rd is the columns consisting of two parts.
```
$output = $formatter->format([
[["Date of purchase", "2020-07-12" ]],                 // row 1
[["Shop", "Aldi Süd" ]],                               // row 2
[["Product", "Tütensuppe Miraculi Familienpackung" ]]  // row 3
]);
```

## Remarks

### Word wrapping 
The algorithm to break columns in multiple lines tries to preserve word boundaries.
Only if it does not find any whitespaces in the column's text, it does hard line feeds
at the last possible character.

## Footnotes 
[1] Please note that one input row does not necessarily output to one row. 
Text could be broken down into several lines to fit the column widths. 

[2] Please note that it usually does not make sense to output a single line in this
case because the border boundaries at top and bottom are added as well.