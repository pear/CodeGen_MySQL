dnl
dnl configure.in helper macros
dnl 
 
dnl 3rd party macro for version number comparisons
m4_include([ax_compare_version.m4])

dnl check for a --with-mysql configure option and set up
dnl MYSQL_CONFIG and MYSLQ_VERSION variables for further use
dnl this must always be called before any other macro from this file
dnl
dnl WITH_MYSQL()
dnl
AC_DEFUN([WITH_MYSQL], [ 
  AC_MSG_CHECKING(for mysql_config executable)

  # try to find the mysql_config script,
  # --with-mysql will either accept its path directly
  # or will treat it as the mysql install prefix and will 
  # search for the script in there
  # if no path is given at all we look for the script in
  # /usr/bin and /usr/local/mysql/bin
  AC_ARG_WITH(mysql, [  --with-mysql=PATH	path to mysql_config binary or mysql prefix dir], [
    if test -x $withval -a -f $withval
    then
      MYSQL_CONFIG=$withval
    elif test -x $withval/bin/mysql_config -a -f $withval/bin/mysql_config
    then 
      MYSQL_CONFIG=$withval/bin/mysql_config
    fi
  ], [
    if test -x /usr/local/mysql/bin/mysql_config -a -f /usr/local/mysql/bin/mysql_config
    then
      MYSQL_CONFIG=/usr/local/mysql/bin/mysql_config
    elif test -x /usr/bin/mysql_config -a -f /usr/bin/mysql_config
    then
      MYSQL_CONFIG=/usr/bin/mysql_config
    fi
  ])

  if test "x$MYSQL_CONFIG" = "x" 
  then
    AC_MSG_ERROR(not found)
  else
    # get installed version
    MYSQL_VERSION=`$MYSQL_CONFIG --version`

	# register replacement vars, these will be filled
    # with contant by the other macros 
	AC_SUBST([MYSQL_CFLAGS])
  	AC_SUBST([MYSQL_CXXFLAGS])
  	AC_SUBST([MYSQL_LDFLAGS])
  	AC_SUBST([MYSQL_LIBS])
  	AC_SUBST([MYSQL_VERSION])

	
    AC_MSG_RESULT($MYSQL_CONFIG)
  fi
])



dnl check if current MySQL version meets a version requirement
dnl and act accordingly
dnl
dnl MYSQL_CHECK_VERSION([requested_version],[yes_action],[no_action])
dnl 
AC_DEFUN([MYSQL_CHECK_VERSION], [
  AX_COMPARE_VERSION([$MYSQL_VERSION], [GE], [$1], [$2], [$3])
])



dnl check if current MySQL version meets a version requirement
dnl and bail out with an error message if not
dnl
dnl MYSQL_NEED_VERSION([need_version])
dnl 
AC_DEFUN([MYSQL_NEED_VERSION], [
  AC_MSG_CHECKING([mysql version >= $1])
  MYSQL_CHECK_VERSION([$1], 
	[AC_MSG_RESULT([yes ($MYSQL_VERSION)])], 
	[AC_MSG_ERROR([no ($MYSQL_VERSION)])])
])



dnl set up variables for compilation of regular C API applications
dnl 
dnl MYSQL_USE_CLIENT_API()
dnl
AC_DEFUN([MYSQL_USE_CLIENT_API], [
  # add regular MySQL C flags
  ADDFLAGS=`$MYSQL_CONFIG --include` 

  MYSQL_CFLAGS="$MYSQL_CFLAGS $ADDFLAGS"    
  MYSQL_CXXFLAGS="$MYSQL_CXXFLAGS $ADDFLAGS"    

  # add linker flags for client lib
  MYSQL_LDFLAGS="$MYSQL_LDFLAGS "`$MYSQL_CONFIG --libs_r`    
])



dnl set up variables for compilation of NDBAPI applications
dnl 
dnl MYSQL_USE_NDB_API()
dnl
AC_DEFUN([MYSQL_USE_NDB_API], [
  MYSQL_USE_API();
  MYSQL_CHECK_VERSION([5.0.0],[  
    # mysql_config results need some post processing for now

    # the include pathes changed in 5.1.x due
    # to the pluggable storage engine clenups
    IBASE=`$MYSQL_CONFIG --include`
    MYSQL_CHECK_VERSION([5.1.0], [
      IBASE="$IBASE/storage/ndb"
    ],[
      IBASE="$IBASE/ndb"
    ])

    # add the ndbapi specifc include dirs
    ADDFLAGS="$ADDFLAGS $IBASE"
    ADDFLAGS="$ADDFLAGS $IBASE/ndbapi"
    ADDFLAGS="$ADDFLAGS $IBASE/mgmapi"

    MYSQL_CFLAGS="$MYSQL_CFLAGS $ADDFLAGS"
    MYSQL_CXXFLAGS="$MYSQL_CXXFLAGS $ADDFLAGS"

    # add the ndbapi specific static libs
    MYSQL_LIBS="$MYSQL_LIBS -lndbclient -lmystrings -lmysys"    
  ],[
    AC_ERROR(["NdbApi needs at lest MySQL 5.0"])
  ])
])



dnl set up variables for compilation of UDF extensions
dnl 
dnl MYSQL_USE_UDF_API()
dnl
AC_DEFUN([MYSQL_USE_UDF_API], [
  # add regular MySQL C flags
  ADDFLAGS=`$MYSQL_CONFIG --include` 

  MYSQL_CFLAGS="$MYSQL_CFLAGS $ADDFLAGS"    
  MYSQL_CXXFLAGS="$MYSQL_CXXFLAGS $ADDFLAGS"    
])



dnl set up variables for compilation of plugins
dnl 
dnl MYSQL_USE_PLUGIN_API()
dnl
AC_DEFUN([MYSQL_USE_PLUGIN_API], [
  # plugin interface is only availabe starting with MySQL 5.1
  MYSQL_NEED_VERSION([5.1.0])

  # for plugins the recommended way to include plugin.h 
  # is <mysql/plugin.h>, not <plugin.h>, so we have to
  # strip thetrailing /mysql from the include paht 
  # reported by mysql_config
  ADDFLAGS=`$MYSQL_CONFIG --include | sed -e"s/\/mysql\$//g"` 

  MYSQL_CFLAGS="$MYSQL_CFLAGS $ADDFLAGS"    
  MYSQL_CXXFLAGS="$MYSQL_CXXFLAGS $ADDFLAGS"    
])


