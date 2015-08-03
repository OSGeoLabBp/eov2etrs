ETRS89 <-> EOV/Balti átszámítás
===============================

Az **ETRS89** földrajzi koordináták (hosszúság és szélesség, ellipszoid feletti magasság)
és az **EOV** koordináták, illetve **Balti** magasság
közötti átszámításra a nyílt forráskódú szoftverek is alkalmasak, de különböző
technikai okok miatt ennek a pontossága néhány méteres csak. 

Az átszámítás pontosítása érdekében a proj.4 programkönyvtár által 
használható javító rácsokat hoztunk létre. A javító rács használható a **cs2cs**
(proj.4 segédprogram), az **ogr2ogr** (OGR segédprogram) és más proj.4 könyvtárat
használó térinformatikai programokkal is mint például **QGIS**, **PostGIS**.
A javítórácsokat letöltheti a honlapunkról (`vízszintes koordinátákhoz tartozó
<http://www.geod.bme.hu/on_line/etrs2eov/etrs2eov_notowgs.gsb>`_, a `magasságokhoz 
tartozó <http://www.geod.bme.hu/on_line/etrs2eov/geoid_eht2014.gtx>`_ )
és a saját gépén is használhatja.

Emellett egy a böngészőből is elérhető **WEB**-es szolgáltatást is létrehoztunk,
mellyel egyesével vagy fájlban tárolt pontokat számíthatunk át a két rendszer 
között.

Miért nem WGS84 koordinátákra készítettük el az átszámítást?
Az európai kontinens mozgása miatt a WGS84 koordináták időben változnak, az
európai referencia rendszer (ETRS89) a kontinenssel együtt mozog, így a
hosszúság és szélesség értékek nem változnak az időben. A WGS84 és az ETRS89 
rendszerek 1989-ben egybeestek, azóta több mint fél méter eltérés alakult ki
köztük.

Közvetlen használat a böngészőben
---------------------------------

A http://www.geod.bme.hu/on_line/etrs2eov címen érhető el a böngészőből
használható átszámítás.

.. image:: images/single.png
   :align: right

Az egymás alatti rádiógombok tartoznak össze, az *Egy pont* és a *Fájl*
opció közötti váltás esetén az űrlap mezők megváltoznak, a két koordináta és a magasság
megadását lehetővé tevő két input mező helyett egy fájl kiválasztó
mező jelenik meg. Az *EOV/Balti->ETR89* illetve az *ETRS89->EOV/Balti* átszámítási irány
módosítása esetén a koordináta mezők előtti feliratok változnak meg.

A *Formátum* mező az átszámítás eredményének formátumát befolyásolja. Csak az 
*EOV/Balti->ETRS89* átszámítás esetén válaszhat több formátum között, mivel a KML és a
GPX formátumok csak földrajzi koordinátákat tartalmazhatnak:

* TXT szóközzel elválasztva jelenik meg a pontszám és a két koordináta illetve a magasság
* KML különböző térinformatikai programokban használható formátum (pl. Google Earth, QGIS, stb.)
* GPX térinformatikai szoftverekben és navigációs GNSS vevőkben használható formátum (pl. Google Earth, QGIS, Garmin GPS vevők, stb.)

Fájlban tárolt pontok átszámítása esetén soronként egy pont adatait kell
megadni szóközzel, tabulátorral vagy pontosvesszővel elválasztva.
Az első mezőbe a pontszámnak, utána pedig a két koordinátának, majd a magasságnak kell következnie.
A koordináták sorrendje felcserélhető a fájlban, például a szélesség megelőzheti
a hosszúságot. A magasságok megadása nem kötelező, üres mező is lehet. A fájlban ezen három adat után 
tetszőleges további adatok szerepelhetnek, ezeket az átszámítás figyelmen kívül hagyja. A numerikus
értékek megadásánál tizedes vesszőt és tizedes pontot is használhat.

Az átszámítás eredménye egy új lapon jelenik meg. Az első oszlopban a
pontszám, a második oszlopban a hoszzúság, illetve az EOV Y koordináta, a
harmadik oszlopban a szélesség, illetve az EOV X koordináta jelenik meg. Ha a bemenő adatok között 
megadtuk az ellipszodi, illetve a tengerszint feletti magasságot, akkor az átszámított magasság az 
eredményében is megjelenik.
Egy pont átszámítása esetén mindig egyes pontszám jelenik meg. Az átszámítás eredményekben mindig
tizedes pontot használ a program, attól függetlenül, hogy mi volt az input adatokban.
Az eredményeket a böngésző program segítségével fájlba mentheti és más
programokban felhasználhatja.

Fájlban tárolt adatok átszámítása esetén a fájl méret maximum 15 MB lehet.

Az általunk készített javítórácsok segítségével az átszámítás 1 cm-en belül megegyzik az EHT2014 
szolgáltatással végzett átszámítással.

WEB-es szolgáltatás használata saját környezetből
-------------------------------------------------

A szerveren futó átszámítást HTTP GET vagy POST kérésekkel is használhatja.
Fájlban tárolt pontok esetén csak POST kérés használható.

A POST illetve GET kérések paraméterei:
* e - EOV y koordináta vagy ETRS hosszúság
* n - EOV x koordináta vagy ETRS szélesség
* h - Balti magasság vagy ellipszoid feletti magasság (opcionális)
* sfradio - értéke **single** vagy **file** lehet
* format - értéke **TXT** vagy **KLM** vagy **GPX** lehet, a KML és GPX formátumok

Például egy EOV koordinátákkal megadott pont átszámítását az alábbi URL megadásával is kezdeményezheti::

    http://www.geod.bme.hu/on_line/etrs2eov/etrs2eov.php?e=650000&n=240000&sfradio=single&format=TXT

Például egy EOV koordinátákkal, illetve Balti magassággal megadott pont átszámítását az alábbi URL megadásával is kezdeményezheti::

    http://www.geod.bme.hu/on_line/etrs2eov/etrs2eov.php?e=650000&n=240000&h=150&sfradio=single&format=TXT

Python programból az alábbi módon érheti el a szolgáltatást (egy pont átszámítása):: 

    >>> import urllib
    >>> req = urllib.urlopen('http://www.agt.bme.hu/on_line/etrs2eov/etrs2eov.php?e=650000&n=240000&sfradio=single&format=TXT').read()
    >>> print req
    1 19.0474474 47.5039331

vagy::
    
    >>> import urllib
    >>> import urllib2
    >>> url = 'http://www.agt.bme.hu/on_line/etrs2eov/etrs2eov.php'
    >>> val = { 'e' : 650000, 'n' : 240000, 'sfradio' : 'single', 'format' : 'TXT' }
    >>> data = urllib.urlencode(val)
    >>> req = urllib2.Request(url, data)
    >>> res = urllib2.urlopen(req)
    >>> print res.read()
    1 19.0474474 47.5039331

Használat cs2cs segédprogramban
-------------------------------

A cs2cs (Coordinate System to Coordinate System) a Proj.4 
programcsomaghoz tartozó parancssori segédprogram.  Windows felhasználók például a
OSGeo4W telepítővel telepíthetik. Segítségével a billentyűzetről bevitt vagy 
fájlban tárolt pontokat számíthatunk át a Proj.4 könyvtár által támogatott
vetületek, koordináta-rendszerek között. A Proj.4 része a vetületi definíciókat
tartalmazó *epsg* szöveg fájl. Ezt linux rendszereken az /usr/share/proj 
könyvtárban találhatjuk meg. Az alábbi példák akkor működnek helyesen, ha a
következő definíció áll az *epsg* fájlban (nincs +towgs!)::

    <23700> +proj=somerc +lat_0=47.14439372222222 +lon_0=19.04857177777778 +k_0=0.99993 +x_0=650000 +y_0=200000 +ellps=GRS67 +units=m +no_defs  <>

A javító rács használatát EOV/Balti -> ETRS89
átszámítás esetén a következő paranccsal kezdeményezhetjük::

   cs2cs -f "%.7f" +init=epsg:23700 +nadgrids=etrs2eov_notowgs.gsb +geoidgrids=geoid_eht2014.gtx +to +init=epsg:4258

Ezután a billentyűzetről vihetjük be az átszámítandó pontok koordinátáit 
soronként, szóközzel elválasztva. Két vagy három koordinátát adhatunk meg.
Fájlban tárolt pontokat a standard input átírányításával dolgozhatunk fel. 
Az eredményeket fájlba írhatjuk a standard output átirányításával.

A fordított irányú átszámítást a következő paranccsal indíthatjuk::

    cs2cs +init=epsg:4258 +to +init=epsg:23700 +nadgrids=etrs2eov_notowgs.gsb +geoidgrids=geoid_eht2014.gtx

Az *epsg* fájl módosíthatjuk, hogy a javító rácsot se kelljen megadni a parancssorban::

    <23700> +proj=somerc +lat_0=47.14439372222222 +lon_0=19.04857177777778 +k_0=0.99993 +x_0=650000 +y_0=200000 +ellps=GRS67 +nadgrids=etrs2eov_notowgs.gsb +geoidgrids=geoid_eht2014.gtx +units=m +no_defs  <>

Ezután nem kell megadni a parancs sorban a rács fájlokat::

    cs2cs +init=epsg:4258 +to +init=epsg:23700

Az átszámítást elvégezhetjük a teljes vetületi definíció megadásával a parancssorban::

     cs2cs +proj=somerc +lat_0=47.14439372222222 +lon_0=19.04857177777778 +k_0=0.99993 +x_0=650000 +y_0=200000 +ellps=GRS67 +nadgrids=etrs2eov_notowgs.gsb +units=m +no_defs +to +init=epsg:4258

A beállításokat ellenőrizhetjük akár a webes alkalmazásunk (http://www.geod.bme.hu/on_line/etrs2eov),
akár akár az EHT2014 (http://gnssnet.hu/EHTClient/) szolgáltatás segítségével.

Használat az ogr2ogr segédprogramban
------------------------------------

Az **ogr2ogr** a GDAL/OGR könyvtárhoz készült segédprogramok egyike. 
Segítségével különböző vektoros formátumok között alakíthatjuk át a 
térinformatikai állományainkat és vetületi átszámítást is végrehajthatunk 
közben. Sajnos az **ogr2ogr** program nem a proj.4 által használt vetületi 
definíciót használja, hanem egy csv fájlt (**pcs.csv** illetve **gcs.csv**),
mely eltérő formátumú is. Ebben nincs hely a javító rács megadására.

Szerencsére a vetületi definíciót az **ogr2ogr** a parancssorból is elfogadja. 
Például egy pontokat tartalmazó ESRI shape fájl átszámítását EOV-ból ETRS89-re::

    ogr2ogr -s_srs "+proj=somerc +lat_0=47.14439372222222 +lon_0=19.04857177777778 +k_0=0.99993 +x_0=650000 +y_0=200000 +ellps=GRS67 +nadgrids=etrs2eov_notowgs.gsb +units=m +no_defs" -t_srs EPSG:4258 -f "ESRI Shapefile" etrs89.shp eov.shp

Vigyázat, a parancsor végén először a cél állomány kell megadni és utána a forrás állományt!

Használat a QGIS programban
---------------------------

A QGIS program a vetületi definíciókat **srs.db** SQLite adatbázisban tárolja. 
Az srs.db fájlt /usr/share/qgis könyvtárban találjuk a Linux rendszereken.
Ezt módosíthatjuk az sqlite3 adatbázis kezelőben az alábbi SQL paranccsal::

    UPDATE tbl_srs SET parameters='+proj=somerc +lat_0=47.14439372222222 +lon_0=19.04857177777778 +k_0=0.99993 +x_0=650000 +y_0=200000 +ellps=GRS67 +nadgrids=etrs2eov_notowgs.gsb +units=m +no_defs' WHERE srid=23700;
   
Emellett saját vetület létrehozása esetén nem kell az SQLite adatbázist 
módosítani. A Beállítások/Egyéni vetület menüpont biztosítja a saját vetület 
bevitelét.

Használat PostGIS programban
----------------------------

A PostGIS a vetületi definíciókat a **spatial_ref_sys** táblában tárolja. 
Ennek tartalmát kell aktualizálnunk az EOV vetületre::

    UPDATE spatial_ref_sys SET proj4text='+proj=somerc +lat_0=47.14439372222222 +lon_0=19.04857177777778 +k_0=0.99993 +x_0=650000 +y_0=200000 +ellps=GRS67 +nadgrids=etrs2eov_notowgs.gsb +units=m +no_defs' WHERE srid=23700;
