.PHONY: all

all::
	phing

test::
	phing test

tar::
	phing tar

docs::
	phing doc

doc::
	$(MAKE) $(MFLAGS) -C doc
	
clean::
	@if test -d ./build/; then rm -rf ./build/; fi
	@find . \( -name \*.rej -o -name \*.orig -o -name .DS_Store -o -name ._\* \) -print -exec rm {} \;
