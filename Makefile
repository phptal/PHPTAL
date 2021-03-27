.PHONY: all

all::test docs doc

test::
	tools/phpunit

doc::
	$(MAKE) $(MFLAGS) -C doc

clean::
	@if test -d ./build/; then rm -rf ./build/; fi
	@find . \( -name \*.rej -o -name \*.orig -o -name .DS_Store -o -name ._\* \) -print -exec rm {} \;
