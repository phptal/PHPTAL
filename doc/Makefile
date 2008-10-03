all: gendoc

gendoc:
	./gendoc.sh

sync: gendoc
	rsync -P --recursive ./build/en/ root@phptal.motion-twin.com:/www/phptal/www/manual/en/
	rsync -P --recursive ./build/ru/ root@phptal.motion-twin.com:/www/phptal/www/manual/ru/

clean:
	-rm -rf build/en
