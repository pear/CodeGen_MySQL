#!/bin/sh
echo
echo "*** testing ... ***"
echo
for test in t/*.test
do
	name=`basename $test .test`
	echo -n "[$name] "
	result=r/$name.result
	mysqltest --database test --test-file=$test --result-file=$result 
done
echo
