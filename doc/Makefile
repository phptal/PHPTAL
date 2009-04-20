LANGS=$(wildcard ??)

SRCLANG=en
BOOK=./$(SRCLANG)/book.xml
STYLESHEET=./temp/new.css

MAKE=make
XMLTO=xmlto
PARMS=-m $(CONFIG)
CONFIG=config.xsl

TEMPBASEDIR=./temp
TEMP=$(TEMPBASEDIR)/$(SRCLANG)
XHTMLFILES=$(TEMP)/xhtml/index.html
XHTMLNOCHUNKSFILES=$(TEMP)/xhtml-nochunks/book.html
TXTFILES=$(TEMP)/txt/book.txt

BUILDBASEDIR=./build
BUILD=$(BUILDBASEDIR)/$(SRCLANG)

# stupid old cruft
.PHONY: all

# stylesheet is here just to speed up parallel build without 3x svn cat
all: $(LANGS)

# recursive make
$(LANGS)::
	@echo "Generating $@"
	$(MAKE) --no-builtin-rules --jobs=2 -$(MAKEFLAGS) SRCLANG="$@" lang

# make a single language given by setting SRCLANG=xx
lang: plaintext onepageperchapter allononepage

onepageperchapter: $(XHTMLFILES) onepageperchapter_css output_dir
	cp -- "$(TEMP)/xhtml/"* "$(BUILD)/split/"

allononepage: $(XHTMLNOCHUNKSFILES) allononepage_css output_dir
	cp -- "$(XHTMLNOCHUNKSFILES)" "$(BUILD)/index.html"
	
plaintext: $(TXTFILES) output_dir
	cp -- "$(TXTFILES)" "$(BUILD)/phptal.txt"

# copy STYLESHEET files
allononepage_css: $(STYLESHEET) output_dir
	cp -- "$(STYLESHEET)" "$(BUILD)/"
	
onepageperchapter_css: $(STYLESHEET) output_dir
	cp -- "$(STYLESHEET)" "$(BUILD)/split"

output_dir:
	@test -d "$(BUILD)/split" || mkdir -p -- "$(BUILD)/split"	

# get STYLESHEET file from font-end
$(STYLESHEET): $(TEMP)
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
	rsync -P --recursive "$(BUILDBASEDIR)/"?? root@phptal.motion-twin.com:/www/phptal/www/manual/

# clean all languages
clean:
	-rm -rf $(BUILDBASEDIR) $(TEMPBASEDIR)

# clean one language
cleanlang:
	@echo "Deleting generated $(SRCLANG)"
	-rm -rf -- "$(TEMP)" "$(BUILD)"

Makefile: ;
