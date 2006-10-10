<?php
/**
 * A class that generates MySQL UDF soure and documenation files
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/Extension.php";

require_once "System.php";
    
require_once "CodeGen/Maintainer.php";

require_once "CodeGen/License.php";

require_once "CodeGen/Tools/Platform.php";

require_once "CodeGen/Tools/Indent.php";

require_once "CodeGen/MySQL/Element/Test.php";

// }}} 

/**
 * A class that generates UDF extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_UDF_MySQL
 */
abstract class CodeGen_MySQL_Extension 
    extends CodeGen_Extension
{
    /** 
     * Custom test cases
     *
     * @var array
     */
    protected $testcases = array();

    /** 
     * Custom library install dir
     *
     * @var string
     */
    protected $libdir = "";

    /**
     * Is the full source needed to compile this?
     *
     * @var bool
     */
    protected $needSource = false;

    // {{{ constructor
    
    /**
     * The constructor
     *
     */
    function __construct() 
    {
        parent::__construct();
    }
    
    // }}} 
    
    // {{{ member adding functions

    /**
     * Do we need the full source to compile? 
     *
     * @param  bool 
     */
    function setNeedSource($flag)
    {
        $this->needSource = (bool)$flag;
    }
    
    // }}} 

    // {{{ output generation


    // {{{ license and authoers
    /**
     * Create the license part of the source file header comment
     *
     * @return string  code fragment
     */
    function getLicense() 
    {    
        $code = "/*\n";
        $code.= "   +----------------------------------------------------------------------+\n";
        
        if (is_object($this->license)) {
            $code.= $this->license->getComment();
        } else {
            $code.= sprintf("   | unkown license: %-52s |\n", $this->license);
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        
        foreach ($this->authors as $author) {
            $code.= $author->comment();
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        $code.= "*/\n\n";
        
        $code.= "/* $ Id: $ */ \n\n";
        
        return $code;
    }
    
    // }}} 


    /**
     * Write authors to the AUTHORS file
     *
     * @access protected
     */
    function writeAuthors() 
    {
        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/AUTHORS");
        if (count($this->authors)) {
            $this->addPackageFile("doc", "AUTHORS");
            echo "{$this->name}\n";
            $names = array();
            foreach ($this->authors as $author) {
                $names[] = $author->getName();
            }
            echo join(", ", $names) . "\n";
        }
        
        return $file->write();
    }


    /**
    * Write EXPERIMENTAL file for non-stable extensions
    *
    * @access protected
    */
    function writeExperimental() 
    {
        if (($this->release) && isset($this->release->state) && $this->release->state !== 'stable') {
            $this->addPackageFile("doc", "EXPERIMENTAL");


            $file =  new CodeGen_Tools_Outbuf($this->dirpath."/EXPERIMENTAL");
?>
this extension is experimental,
its functions may change their names 
or move to extension all together 
so do not rely to much on them 
you have been warned!
<?php

            return $file->write();
        }
    }


    /** 
    * Generate NEWS file (custom or default)
    *
    * @access protected
    */
    function writeNews() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/NEWS");

?>
This is a standalone UDF extension created using CodeGen_Mysql_UDF <?php echo $this->version(); ?>

...
<?php

        return $file->write();
    }


    /** 
    * Generate ChangeLog file (custom or default)
    *
    * @access protected
    */
    function writeChangelog() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/ChangeLog");
?>
This is a standalone UDF extension created using CodeGen_Mysql_UDF <?php echo $this->version(); ?>

...
<?php

        $file->write();
    }


    /**
     * Create the extensions including
     *
     * @param  string Directory to create (default is ./$this->name)
     */
    function createExtension($dirpath = false, $force = false) 
    {
        // default: create dir in current working directory, 
        // dirname is the extensions base name
        if (empty($dirpath) || $dirpath == ".") {
            $dirpath = "./" . $this->name;
        } 
        
        // purge and create extension directory
        if (file_exists($dirpath)) {
            if ($force) {
                if (!is_writeable($dirpath) || !@System::rm("-rf $dirpath")) {
                    return PEAR::raiseError("can't purge '$dirpath'");
                }
            } else {
                return PEAR::raiseError("'$dirpath' already exists, can't create that directory (use '--force' to override)"); 
            }
        }
        if (!@System::mkdir("-p $dirpath")) {
            return PEAR::raiseError("can't create '$dirpath'");
        }
        
        // make path absolute to be independant of working directory changes
        $this->dirpath = realpath($dirpath);
        
        echo "Creating '{$this->name}' extension in '$dirpath'\n";
        
        // generate complete source code
        $this->generateSource();
        
        // generate README file
        $this->writeReadme();

        // write test files
        $this->writeTests();

        // generate INSTALL file
        $this->writeInstall();

        // generate NEWS file
        $this->writeNews();
        
        // generate ChangeLog file
        $this->writeChangelog();

        // generate AUTHORS file
        $this->writeAuthors();

        // copy additional source files
        if (isset($this->packageFiles['copy'])) {
            foreach ($this->packageFiles['copy'] as $basename => $filepath) {
                copy($filepath, $this->dirpath."/".$basename);
            }
        }

        // let autoconf and automake take care of the rest
        $olddir = getcwd();
        chdir($this->dirpath);

        $return = 0;
        
        echo "\nRunning 'aclocal'\n";
        system("aclocal", $return);

        if ($return === 0) {
            echo "\nRunning 'autoconf'\n";
            system("autoconf", $return);
        }

        if ($return === 0) {
            echo "\nRunning 'libtoolize'\n";
            system("libtoolize --automake", $return);
        }

        if ($return === 0) {
            echo "\nRunning 'automake'\n";
            system("automake --add-missing", $return);
        }

        chdir($olddir);

        if ($return != 0) {
            return PEAR::raiseError("autotools failed");
        }

        return true;
    }
    
    /**
     * Generate configure files for this extension
     *
     * @access protected
     */
    function writeConfig() {
        // copy .m4 include files
        foreach (glob("@DATADIR@/CodeGen_MySQL/*.m4") as $file) {
            copy($file, $this->dirpath."/".basename($file));
        }

        // Makefile.am
        $makefile = new CodeGen_Tools_Outbuf($this->dirpath."/Makefile.am");

        if (!empty($this->libdir)) {
            echo "libdir={$this->libdir}\n";
        }
        echo "lib_LTLIBRARIES = {$this->name}.la\n";
        echo "{$this->name}_la_CFLAGS = @MYSQL_CFLAGS@\n";
        echo "{$this->name}_la_CXXFLAGS = @MYSQL_CFLAGS@\n";
        echo "{$this->name}_la_LDFLAGS = -module -avoid-version -no-undefined\n";
        echo "{$this->name}_la_SOURCES = {$this->name}.".$this->language;
        if (isset($this->packageFiles['code'])) {
            foreach ($this->packageFiles['code'] as $file) {
                echo " ".basename($file);
            }
        }
        echo "\n";
        echo "test: all\n";
        echo "\tcd tests; ./test.sh\n";        

        $makefile->write();
    
        
        // acinclude.m4
        $acinclude = new CodeGen_Tools_Outbuf($this->dirpath."/acinclude.m4");
        foreach ($this->acfragments["top"] as $fragment) {
            echo "$fragment\n";
        }        
        echo "m4_include([mysql.m4])\n";
        foreach ($this->acfragments["bottom"] as $fragment) {
            echo "$fragment\n";
        }        
        $acinclude->write();


        // configure.in
        $configure = new CodeGen_Tools_Outbuf($this->dirpath."/configure.in");

        echo "AC_INIT({$this->name}.".$this->language.")\n";
        echo "AM_INIT_AUTOMAKE(UDF-{$this->name}, ". $this->release->getVersion() .")\n";
        echo "\n";

        foreach ($this->configfragments['top'] as $fragment) {
            echo "$fragment\n";
        }
        
        echo "AC_PROG_LIBTOOL\n";

        echo "AC_PROG_CC\n";
        if ($this->language === "cpp") {
            echo "AC_PROG_CXX\n";
            echo "AC_LANG([C++])\n";
        }

        if ($this->needSource) {
            echo "WITH_MYSQL_SRC()\n";
        } else {
            echo "WITH_MYSQL()\n";
        }      

        foreach ($this->configfragments['bottom'] as $fragment) {
            echo "$fragment\n";
        }

        echo "MYSQL_SUBST()\n";

        echo "AC_OUTPUT(Makefile)\n";

        $configure->write();
    }

    /**
     * Create the extensions code soure and project files
     *
     * @access  protected
     */
    function generateSource() 
    {
        // generate source and header files
        $this->writeHeaderFile();
        $this->writeCodeFile();

        // generate .cvsignore file entries
        $this->writeDotCvsignore();

        // generate EXPERIMENTAL file for unstable release states
        $this->writeExperimental();
        
        // generate LICENSE file if license given
        if ($this->license) {
            $this->license->writeToFile($this->dirpath."/COPYING");
            $this->files['doc'][] = "COPYING";
        }

        // generate autoconf/automake files
        $this->writeConfig();
    }


    /**
     * add a custom test case
     *
     * @access public
     * @param  object  a Test object
     */
    function addTest(CodeGen_MySQL_Element_Test $test) 
    {
        $name = $test->getName();
       
        if (isset($this->testcases[$name])) {
            return PEAR::raiseError("testcase '{$name}' added twice");
        }

        $this->testcases[$name] = $test;
        return true;
    }

    /**
     * Create test files
     *
     * @void
     */
    function writeTests()
    {
        @mkdir($this->dirpath."/tests");
        @mkdir($this->dirpath."/tests/t");
        @mkdir($this->dirpath."/tests/r");

        copy("@DATADIR@/CodeGen_MySQL/test.sh", $this->dirpath."/tests/test.sh");

        chmod($this->dirpath."/tests/test.sh", 0555);

        // custom test cases (may overwrite custom function test cases)
        foreach ($this->testcases as $test) {
            $test->writeTest($this);
        }
    }

    function testFactory()
    {
        return new CodeGen_MySQL_Element_Test(); 
    }
}   


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
