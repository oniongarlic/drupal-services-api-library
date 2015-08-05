PHPLIB=DrupalServiceAPIClient.class.php
PHPTEST=test-node.php test-file.php test-user.php

check: check-lib check-test

check-lib:
	cd lib && php -l $(PHPLIB)

check-test:
	cd test&& php -l $(PHPTEST)
