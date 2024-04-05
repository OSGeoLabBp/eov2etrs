# Centiméter pontosságú átszámítás ETRS89 – EOV/EOMA rendszerek között – ArcGIS Pro felhasználók részére

1. Zárjon be minden ArcGIS alkalmazást! (Az ArcGIS Pro összes példányát, valamint az ArcMap/ArcCatalog/ArcScene/ArcGlobe összes példányát is!)
2. Telepítse az „ArcGIS Coordinate Systems Data” nevű csomagot! Az állomány meglehetősen nagy, de a telepítés során bőven elegendő, ha csak Európa transzformációs rácsait telepítjük. Ebben az esetben kb. 3 GiB helyett csak kb. 400–500 MiB-et foglal. A csomag alapértelmezetten a C:\Program Files (x86)\ArcGIS\CoordinateSystemsData mappába kerül.
3. Navigáljon a C:\Program Files (x86)\ArcGIS\CoordinateSystemsData\pedata mappába és másolja ide a „geoid_eht2014.bin”, valamint a „etrs2eov_notowgs.gsb” nevű fájlt!
4. Másolja a C:\Users<saját felhasználónév>\AppData\Roaming\Esri\ArcGISPro\ArcToolbox\CustomTransformations mappába az „ETRS_1989_To_EOMA_1980_Custom_EHT2014_Geoid.vtf” és a „Hungarian_1972_To_ETRS_1989_Custom_NTv2.gtf” fájlt!
5. Ezzel a telepítés kész, a transzformáció használható. A működőképességét ellenőrizze le egy ismert ponton, pl.: https://www.gnssnet.hu/pdf/MPOG.pdf!
