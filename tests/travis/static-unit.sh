#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

./vendor/bin/phpcs ./src --standard=./tests/static/phpcs-ruleset.xml -p -n
./vendor/bin/phpmd ./src xml ./tests/static/phpmd-ruleset.xml
./vendor/bin/phpunit --configuration ./tests/unit --coverage-clover ./tests/unit/tmp/clover.xml && php ./tests/unit/code-coverage.php ./tests/unit/tmp/clover.xml
./vendor/bin/phpunit --configuration ./tests/unit
