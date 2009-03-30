all: gendoc

gendoc:
	./gendoc.sh

sync: gendoc
	rsync -P --recursive ./build/?? root@phptal.motion-twin.com:/www/phptal/www/manual/

clean:
	-rm -rf build/?? ??/xhtml ??/xhtmlonepage ??/plaintext
