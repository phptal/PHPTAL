pos=`find -name "*.po"`
for po in $pos
do
	mo=`echo $po | sed 's/po/mo/'`
	echo $po "->" $mo
	msgfmt $po -o $mo
done
