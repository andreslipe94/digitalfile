#!/bin/sh

if [ "$testsuite" = "API" ]; then
	TEST_IMG_PATH="tests/tmp/test.png"
else
	echo "[Error] No such testsuite: '$testsuite'"
	exit 1
fi
