XMLTO=/usr/bin/xmlto
PARMS=-m config.xsl
BOOK=book.xml

all:split onepage plaintext

split:
	$(XMLTO) $(PARMS) -o xhtml xhtml $(BOOK)

onepage:
	$(XMLTO) $(PARMS) -o xhtmlonepage xhtml-nochunks $(BOOK)

plaintext:
	$(XMLTO) $(PARMS) -o plaintext txt $(BOOK)

