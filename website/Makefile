all:
	php -q make.php

deploy:
	cleanup
	rsync -avz \
	--exclude=".svn" \
	--exclude="files/.svn" \
	--exclude="files/old/.svn" \
	--exclude="*~" \
	--exclude=".*.swp" \
	--rsh="ssh -l phptal" www/ phptal@phptal.motiontwin.com:www/
	ssh phptal@phptal.motiontwin.com "find www -type d | xargs chmod 755"
	ssh phptal@phptal.motiontwin.com "find www -type f | xargs chmod 644"

