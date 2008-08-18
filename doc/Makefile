all: gendoc

gendoc:
	./gendoc.sh

sync: gendoc
	rsync -P --recursive ./build/en/ root@phptal.motion-twin.com:/www/phptal/www/manual/en/

clean:
	-rm -rf build/en
