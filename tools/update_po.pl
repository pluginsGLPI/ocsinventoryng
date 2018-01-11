#!/usr/bin/perl
#!/usr/bin/perl -w 

if (@ARGV!=2){
print "USAGE update_po.pl transifex_login transifex_password\n\n";

exit();
}
$user = $ARGV[0];
$password = $ARGV[1];

opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `wget --user=$user --password=$password --output-document=locales/$_ http://www.transifex.com/api/2/project/GLPI_ocsinventoryng/resource/glpi_ocsinventoryng-version-100/translation/$lang/?file=$_`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  
