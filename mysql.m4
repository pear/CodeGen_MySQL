dnl
dnl configure.in helper macros
dnl 
 
m4_include([ax_compare_version.m4])

AC_DEFUN([MYSQL_CHECK_VERSION], [
  AX_COMPARE_VERSION([$MYSQL_VERSION], [GE], [$1], 
	[AC_MSG_RESULT([yes ($MYSQL_VERSION)])], 
	[AC_MSG_ERROR([no ($MYSQL_VERSION)])])
])


AC_DEFUN([MYSQL_NEED_VERSION], [
  AC_MSG_CHECKING([mysql version >= $1])
  MYSQL_CHECK_VERSION([$1], 
	[AC_MSG_RESULT([yes ($MYSQL_VERSION)])], 
	[AC_MSG_ERROR([no ($MYSQL_VERSION)])])
])

AC_DEFUN([WITH_MYSQL], [ 
  AC_MSG_CHECKING(for mysql_config executable)

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
    AC_MSG_RESULT(not found)
    exit 3
  else
    # get installed version
    MYSQL_VERSION=`$MYSQL_CONFIG --version`
	
    AC_MSG_RESULT($MYSQL_CONFIG)
  fi
])

AC_DEFUN([MYSQL_USE_CLIENT_API], [
  # add regular MySQL C flags
  ADDFLAGS=`$MYSQL_CONFIG --include` 

  MYSQL_CFLAGS="$MYSQL_CFLAGS $ADDFLAGS"    
  MYSQL_CXXFLAGS="$MYSQL_CXXFLAGS $ADDFLAGS"    

  # add linker flags for client lib
  MYSQL_LDFLAGS="$MYSQL_LDFLAGS "`$MYSQL_CONFIG --libs_r`    
])

AC_DEFUN([MYSQL_USE_UDF_API], [
  # add regular MySQL C flags
  ADDFLAGS=`$MYSQL_CONFIG --include` 

  MYSQL_CFLAGS="$MYSQL_CFLAGS $ADDFLAGS"    
  MYSQL_CXXFLAGS="$MYSQL_CXXFLAGS $ADDFLAGS"    
])



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

AC_DEFUN([MYSQL_AC_SUBST], [
  AC_SUBST([MYSQL_CFLAGS])
  AC_SUBST([MYSQL_CXXFLAGS])
  AC_SUBST([MYSQL_LDFLAGS])
  AC_SUBST([MYSQL_LIBS])
])

