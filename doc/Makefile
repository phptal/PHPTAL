LANGS=$(wildcard ??)

SRCLANG=en
BOOK=./$(SRCLANG)/book.xml
STYLESHEET=./temp/new.css

MAKE=make
XMLTO=xmlto
PARMS=-m $(CONFIG)
CONFIG=config.xsl

TEMP=./temp/$(SRCLANG)
XHTMLFILES=$(TEMP)/xhtml/index.html
XHTMLNOCHUNKSFILES=$(TEMP)/xhtml-nochunks/book.html
TXTFILES=$(TEMP)/txt/book.txt

BUILDDIR=./build
BUILD=$(BUILDDIR)/$(SRCLANG)

# stupid old cruft
.PHONY: all

all: $(LANGS)

# recursive make
$(LANGS)::
	@echo "Generating $@"
	$(MAKE) --no-builtin-rules -$(MAKEFLAGS) SRCLANG="$@" lang

# make a single language given by setting SRCLANG=xx
lang: onepageperchapter allononepage plaintext

onepageperchapter: $(XHTMLFILES) onepageperchapter_css
	@test -d "$(BUILD)/split" || mkdir -p -- "$(BUILD)/split"
	cp -- "$(TEMP)/xhtml/"* "$(BUILD)/split/"

allononepage: $(XHTMLNOCHUNKSFILES) allononepage_css
	cp -- "$(TEMP)/xhtml-nochunks/book.html" "$(BUILD)/index.html"
	
plaintext: $(TXTFILES)
	cp -- "$(TEMP)/txt/book.txt" "$(BUILD)/phptal.txt"

# copy STYLESHEET files
allononepage_css: $(STYLESHEET)
	cp -- "$(STYLESHEET)" "$(BUILD)/"
	
onepageperchapter_css: $(STYLESHEET)
	cp -- "$(STYLESHEET)" "$(BUILD)/split"

# get STYLESHEET file from font-end
$(STYLESHEET):
	svn cat https://svn.motion-twin.com/phptal/website/www/new.css > "$(STYLESHEET)"

# create temp output directory for current language
$(TEMP):
	@echo "Creating $(TEMP)"
	@test -d "$(TEMP)" || mkdir -p -- "$(TEMP)"

# one page per chapter (chunked)
$(XHTMLFILES): $(TEMP) $(BOOK) $(CONFIG)
	$(XMLTO) $(PARMS) -o "$(TEMP)/xhtml" xhtml -- "$(BOOK)"

# all on one page
$(XHTMLNOCHUNKSFILES): $(TEMP) $(BOOK) $(CONFIG)
	$(XMLTO) $(PARMS) -o "$(TEMP)/xhtml-nochunks" xhtml-nochunks -- "$(BOOK)"

# plaintext
$(TXTFILES): $(TEMP) $(BOOK) $(CONFIG)
	$(XMLTO) $(PARMS) -o "$(TEMP)/txt" txt -- "$(BOOK)"

sync:
	rsync -P --recursive "$(BUILDDIR)/"?? root@phptal.motion-twin.com:/www/phptal/www/manual/

# clean all languages
clean:
	@for l in $(LANGS); do $(MAKE) SRCLANG="$$l" cleanlang; done;
	-rmdir $(BUILDDIR)

cleanlang:
	@echo "Deleting generated $(SRCLANG)"
	-rm -rf -- "$(TEMP)" "$(BUILD)"
