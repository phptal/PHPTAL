all:
	php -q make.php

deploy:
	cleanup
	rsync -avz --rsh="ssh -l lbedubourg -p 222" www/ lbedubourg@motion-twin.com:www/
	ssh -p 222 lbedubourg@motion-twin.com "find www -type d | xargs chmod 755"
	ssh -p 222 lbedubourg@motion-twin.com "find www -type f | xargs chmod 644"

