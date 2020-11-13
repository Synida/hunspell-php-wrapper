[![Build Status](https://travis-ci.org/Synida/hunspell-php-wrapper.svg?branch=master)](https://travis-ci.org/Synida/hunspell-php-wrapper)
[![Coverage Status](https://coveralls.io/repos/github/Synida/hunspell-php-wrapper/badge.svg)](https://coveralls.io/github/Synida/hunspell-php-wrapper)

# hunspell-php-wrapper
This library is a simple Hunspell spellchecker wrapper with multithreading support for spellchecking 
and a simple dictionary editor.

### Requirements

Hunspell - you won't be able to use this library without it.

If you wish like to use this module in multithreading mode, then you need the following too:
- PHP ^7.2
- PHP with ZTS enabled
- [parallel](https://github.com/krakjoe/parallel) extension - you might have to compile it from source

### Installation

```
composer require synida/hunspell-php-wrapper
```

During the installation a script will detect the CPU threads and the on the PC.
Similarly, a minimum word per thread will be calculated, which will determine what is the minimum number of words 
from where it's worth using an extra thread (*with global average 91% accurate typing rate*)

This installation script will use the parallel module if it's installed,
otherwise the thread number will be set to 1.

You may install your own dictionaries for the hunspell.

## Spellchecker

### Basic usage:

```
$results = (new HunSpell(
    // encoding
    'en_GB.utf8',
    // dictionary file name without extension 
    'en_GB', 
    // response type
    HunSpell::JSON_RESPONSE, 
    // max threads - ignored if the parallel module is dissabled
    1, 
    // optimal minimum thread words - ignored if the thread number is 1
    Configuration::MIN_WORD_PER_THREAD
))->suggest();
```

Supported output formats: `array`, `json`

The output has an optimized size, if you are not sure what the attribute keys mean, 
you can find the key constants in the `HunSpell` class

### Advanced usage:

**The parallel extension can't be used in web (*PHP CGI*) environment, thus the wrapper can be used only with PHP CLI too and cannot be used directly in web environment**

However, if you run a php script in a daemon(for example [supervisor](http://supervisord.org/introduction.html))
then you can communicate between the script(*running by PHP CLI*), and your web environment(*PHP CGI*) through a UDP port or
a unix socket, thus you can use the multithreading function on the web environment.
For optimized performance it's recommended to use single thread execution under the minimum optimal thread words,
which should be adjusted with the communication time(*which should be measured*) between the background script and the web app.

### Good to know

It takes around 10 times as much time for an average word to calculate its suggestions for the Hunspell,
then checking a correct word.

Creating individual threads takes time. Using the optimal minimum words per threads will barely increase the spell checking time
if the spellchecked text is very short. Although the performance will increase with longer texts. 
By short text I mean something very close to `Configuration::MIN_WORD_PER_THREAD * n` threads
(if it was calculated by you OR during the composer install, meaning the parallel was installed before that).

## Dictionary editor

Each dictionary have two files; a `.dic` and a `.aff` file.
The .dic file starts with a number in the first line - this is the number of words the dictionary contains.
The rest is the words of the dictionary. The .aff file is a ruleset for the dictionary, which describes language rules.

The hunspell can use one dictionary at once, and it's much slower to execute the spellchecking twice on two smaller
dictionaries and merge the results, then execute it on one bigger dictionary.

The default dictionary paths for hunspell can be checked by using the `hunspell -D` command.

### Dictionary editor basic usage:

**[Note]:**
File and word operations require rw file permission.

To create dictionary files(`.dic`/`.aff`/`.tpl`):
```
(new DictionaryEditor())->create($path);
```

To delete dictionary files(`.dic`/`.aff`/`.tpl`):
```
(new DictionaryEditor())->delete($path);
```

Adding new word to a dictionary/template:
```
$dictionaryEditor = new DictionaryEditor();

if (!$dictionaryEditor->addWord($path, $word)) {
    echo $dictionaryEditor->getMessage();
}
```

**[Note]:** Adding a word to a dictionary template(`.tpl`) won't add word count in the first line of the template, 
like it does for the dictionary, which is required for the correct functionality

**[Note]:** If the word exists in the dictionary or template, then the word won't be added again,
nor exception will be generated, but the DictionaryEditor class' message property will be set

Deleting word from a dictionary/template:
```
$dictionaryEditor = new DictionaryEditor();

if (!$dictionaryEditor->deleteWord($path, $word)) {
    echo $dictionaryEditor->getMessage();
}
```

Editing word in a dictionary/template:
```
$dictionaryEditor = new DictionaryEditor();

if (!$dictionaryEditor->editWord($path, $word, $modifiedWord)) {
    echo $dictionaryEditor->getMessage();
}
```

**[Note]:**
Target word can't be already existing dictionary word

Listing words:
```
(new DictionaryEditor())->listWords($path);
```

### Advanced usage:

Using one dictionary is way faster, then executing the hunspell on multiple dictionaries. 
Editing language dictionaries are sometimes not a good idea, 
because the added/edited words will be gone as you upgrade it.
Instead of editing the dictionaries directly, you can use templates, where you store your word and where you generate
the dictionaries from. For example if you want an upgraded language dictionary(`upgraded_en_GB.dic`), then you can keep a
language dictionary(`en_GB.dic`) and a template for your upgrade(`custom_words.tpl`). 
When you change the template files you can, you can relatively fast change the target word in the cache file/files - 
like the `upgraded_en_GB.dic` file - too. This implementation can be extended with multiple levels. 

If you wish to store the dictionary files in the project folder, you can change the default folder paths in the
source code of the Hunspell(`hunspell-master/src/tools/hunspell.cxx`), and recompile it like that.
