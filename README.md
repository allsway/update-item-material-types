# update-item-material-types
Update item material types from current material type to mapping from Sierra/Millennium ITYPE values that were migrated over to Alma as a note

#### update_mattype.ini
configuration file for Alma API key, Alma campus code and item record field that contains your former system ITYPE code mapping

```
;Enter you Alma API key here
apikey = ""
;Base Alma API url
baseurl = "https://api-na.hosted.exlibrisgroup.com"
;Enter your Alma campus code
campuscode = ""
;enter the item record field that you used to map your former system ITYPE values to
itype_location = "internal_note_3"
```

#### update_mattype.php
Takes as arguments: 
   - [1] a csv file with the ITYPE to Alma material type mapping 
   - [2] an item export file from Alma in csv format, used to obtain the item MMS ID, holding MMS ID and bib MMS ID

Run as `php update_mattype.php itype_mapping.csv item_data.csv`
 
  

