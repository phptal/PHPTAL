LANGS=$(wildcard ??)

SRCLANG=en
BOOK=./$(SRCLANG)/book.xml

MAKE=make
XMLTO=xmlto
PARMS=-m $(CONFIG)
CONFIG=config.xsl

TEMPBASEDIR=./temp
TEMP=$(TEMPBASEDIR)/$(SRCLANG)
XHTMLFILES=$(TEMP)/xhtml/
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

onepageperchapter: $(XHTMLFILES) output_dir
	cp -p -- "$(TEMP)/xhtml/"* "$(BUILD)/split/"

allononepage: $(XHTMLNOCHUNKSFILES) output_dir
	cp -p -- "$(XHTMLNOCHUNKSFILES)" "$(BUILD)/"
	
plaintext: $(TXTFILES) output_dir
	cp -p -- "$(TXTFILES)" "$(BUILD)/phptal.txt"

output_dir:
	@test -d "$(BUILD)/split" || mkdir -p -- "$(BUILD)/split"	

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

# clean all languages
clean:
	-rm -rf $(BUILDBASEDIR) $(TEMPBASEDIR)

# clean one language
cleanlang:
	@echo "Deleting generated $(SRCLANG)"
	-rm -rf -- "$(TEMP)" "$(BUILD)"

Makefile: ;
