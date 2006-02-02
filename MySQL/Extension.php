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
            foreach($this->authors as $author) {
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
        // Makefile.am
        $makefile = new CodeGen_Tools_Outbuf($this->dirpath."/Makefile.am");

        echo "lib_LTLIBRARIES = {$this->name}.la\n";
        echo "{$this->name}_la_CFLAGS = @MYSQL_CFLAGS@\n";
        echo "{$this->name}_la_CXXFLAGS = @MYSQL_CFLAGS@\n";
        echo "{$this->name}_la_LDFLAGS = -module -avoid-version -no-undefined\n";
        echo "{$this->name}_la_SOURCES = {$this->name}.".$this->language;
        if (isset($this->packageFiles['source'])) {
            foreach ($this->packageFiles['source'] as $file) {
                echo " ".basename($file);
            }
        }
        echo "\n";

        $makefile->write();
    


        // configure.in
        $configure = new CodeGen_Tools_Outbuf($this->dirpath."/configure.in");

        echo "AC_INIT({$this->name}.".$this->language.")\n";
        echo "AM_INIT_AUTOMAKE({$this->name}.so, 1.0)\n";
        echo "\n";
        
        echo "AC_PROG_LIBTOOL\n";

        if ($this->language === "cpp") {
            echo "AC_PROG_CXX\n";
        }

        foreach ($this->configfragments['top'] as $fragment) {
            echo "$fragment\n";
        }

        echo '
searchin="/usr /usr/local /usr/local/mysql"
AC_ARG_WITH(mysql, 
    AC_HELP_STRING([--with-mysql=PATH], [path to mysql_config or mysql install dir]), 
    [
      case $withval in
        (yes)
          mysql_config=""
        ;;
        (no)
          mysql_config=""
        ;;
        (*)
          mysql_config="$withval"
        ;;  
      esac
    ], 
    [])

AC_MSG_CHECKING(for mysql_config)

if test -z "$mysql_config"
then
  if ! mysql_config=`which mysql_config`
  then
    AC_MSG_ERROR(mysql_config not found in PATH, please use --with-mysql=...)
  fi
else
  if test -d "$mysql_config"
  then
    if test -x "$mysql_config/bin/mysql_config"
    then
      mysql_config="$mysql_config/bin/mysql_config"
    else
      AC_MSG_ERROR($mysql_config not found or not executable)
    fi
  fi
fi

if test -z "$mysql_config"
then
  AC_MSG_ERROR(UDFs require mysql_config to detect needed CFLAGS)
fi

AC_MSG_RESULT($mysql_config)

AC_MSG_CHECKING(for MySQL CFLAGS) 

if ! mysql_cflags=`$mysql_config --udf-cflags 2>/dev/null`
then
  if ! mysql_cflags=`$mysql_config --cflags 2>/dev/null`
  then
    AC_MSG_ERROR(cannot detect --udf-cflags or --cflags)
  fi
fi

AC_MSG_RESULT($mysql_cflags)

AC_ARG_WITH(debug,
    [  --with-debug            Build test version with debugging code],
    [with_debug=$withval],
    [with_debug=no])
if test "$with_debug" = "yes"
then
  mysql_cflags="$mysql_cflags -DDEBUG -DDBUG_ON"
else
  mysql_cflags="$mysql_cflags -DNDEBUG -DDBUG_OFF"
fi

AC_SUBST(MYSQL_CFLAGS, $mysql_cflags)

AC_OUTPUT(Makefile)
';

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
    
}   


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
