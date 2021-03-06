<?php
//set_time_limit(108000);
//////////// Compilation ///////////////////// 
echo "\nCompilation";
include $file_timer_begin;

$link = mysqli_connect ($host,$user,$pass,$db) or die ('Erreur : '.mysqli_error());
mysqli_query($link,"SET NAMES 'utf8'");

mysqli_query($link,"TRUNCATE `artworks`");
mysqli_query($link,"TRUNCATE `artw_prop`");
mysqli_query($link,"TRUNCATE `label_page`");
mysqli_query($link,"TRUNCATE `p31`");
mysqli_query($link,"TRUNCATE `p135`");
mysqli_query($link,"TRUNCATE `p136`");
mysqli_query($link,"TRUNCATE `p144`");
mysqli_query($link,"TRUNCATE `p170`");
mysqli_query($link,"TRUNCATE `p179`");
mysqli_query($link,"TRUNCATE `p180`");
mysqli_query($link,"TRUNCATE `p186`");
mysqli_query($link,"TRUNCATE `p195`");
mysqli_query($link,"TRUNCATE `p276`");
mysqli_query($link,"TRUNCATE `p361`");
mysqli_query($link,"TRUNCATE `p921`");
mysqli_query($link,"TRUNCATE `p941`");
mysqli_query($link,"TRUNCATE `p1639`");
mysqli_query($link,"ALTER TABLE `commons_img` ADD INDEX(`P18`)");

$tab_lg=array("ar","bn","br","ca","cs","de","el","en","eo","es","fa","fi","fr","he","hi","id","it","ja","jv","ko","nl","pa","pl","pt","ru","sw","sv","te","th","tr","uk","vi","zh");

$dirname = $fold_crotos.'harvest/items/';
$dir = opendir($dirname); 
$cpt=0;
while($file = readdir($dir)) {
	//Test if ($cpt==20) break;  
	if($file != '.' && $file != '..' && !is_dir($dirname.$file)){
		$item=str_replace(".json","",$file);
		$cpt++;
		//Test echo $cpt." ".$item." | ";
		
		$tab_miss = array(
			"m135"=> 0,// movement
			"m136"=> 0,// genre
			"m144"=> 0,// based on
			"m180"=> 0,// depicts
			"m179"=> 0,// series
			"m170"=> 0,// creator
			"m186"=> 0,// material
			"m195"=> 0,// collection
			"m276"=> 0,// location
			"m361"=> 0,// part of
			"m921"=> 0,// subject heading
			"m941"=> 0,// inspired by
			"ar"=> 0,
			"bn"=> 0,
			"br"=> 0,
			"ca"=> 0,
			"cs"=> 0,
			"de"=> 0,
			"el"=> 0,
			"en"=> 0,
			"eo"=> 0,
			"es"=> 0,
			"fa"=> 0,
			"fi"=> 0,
			"fr"=> 0,
			"he"=> 0,
			"hi"=> 0,
			"id"=> 0,
			"it"=> 0,
			"ja"=> 0,
			"jv"=> 0,
			"ko"=> 0,
			"mu"=> 0,
			"nl"=> 0,
			"pa"=> 0,
			"pl"=> 0,
			"pt"=> 0,
			"ru"=> 0,
			"sw"=> 0,
			"sv"=> 0,
			"te"=> 0,
			"th"=> 0,
			"tr"=> 0,
			"uk"=> 0,
			"vi"=> 0,
			"zh"=> 0
		);
		if (($cpt % 1000)==0)
		echo "\n$cpt";

$datafic=file_get_contents("items/$item.json",true);
$data = json_decode($datafic,true);

$varlab=$data["entities"]["Q".$item];
$claims=$varlab["claims"];


$tab_prop = array(
	"P18"=> "",  // Image
	"P214"=> "", // VIAF ID
	"P217"=> "", // Inventory number
	"P347"=> "", // Joconde ID
	"P350"=> "", // RKDimages ID
	"P373"=> "", // Commons Category
	"P727"=> "", // Europeana ID
	"P856"=> "", // Official website
	"P973"=> "", // described at URL
	"P1212"=> "" // Atlas ID
);

foreach($tab_prop as $key=>$val){
	if ($claims[$key]){
		foreach ($claims[$key] as $value){
			if ($tab_prop[$key]=="")
			   $tab_prop[$key]=$value["mainsnak"]["datavalue"]["value"];
			else
				break;
		}
		if ($key!="P18")
			$tab_prop[$key]=esc_dblq($tab_prop[$key]);
	}
}

$new_img=0;
$p18=0;
$hd=0;
if ($tab_prop["P18"]!=""){
	$img_exists=false;
	$sql="SELECT id FROM commons_img WHERE P18=\"".esc_dblq($tab_prop["P18"])."\"";
	$rep=mysqli_query($link,$sql);
	if (mysqli_num_rows($rep)!=0)
		$img_exists=true;		
	$p18=id_commons($tab_prop["P18"]);
	
	if ($p18!=0){
		$sql="SELECT width,height FROM commons_img WHERE id=".$p18;
		$rep=mysqli_query($link,$sql);
		if (mysqli_num_rows($rep)!=0){
			$row = mysqli_fetch_assoc($rep);
			if (($row['width']>=2000)||($row['height']>=2000))
				$hd=1;
		}
	}
	$id_artwork=$row['id'];
	
	if (($p18!=0)and(!($img_exists)))
		$new_img=1;
}

//date P571 or P585
$year1 = NULL;
$year2 = NULL;
$b_date=0;
$tab_date=array("P571","P585");
for ($i=0;$i<count($tab_date);$i++){
	if ($claims[$tab_date[$i]]){
		foreach ($claims[$tab_date[$i]] as $value){
			$time=$value["mainsnak"]["datavalue"]["value"]["time"];
			$precision=$value["mainsnak"]["datavalue"]["value"]["precision"];
			$after=$value["mainsnak"]["datavalue"]["value"]["after"];
			$date_tmp=intval(substr($time,1,strpos($time,"-")-1));
			if ((intval($precision<9))&&(intval($after)==0))
				$b_date=1;
			if (substr($time,0,1)=="-")
				$date_tmp = -1 * abs($date_tmp);
			if (($b_date==0)){
				$gap=9-intval($precision);
				switch ($gap) {
					case 0:
						$coef=0;
						break;
					case 1:
						$coef=10;
						break;
					case 2:
						$coef=100;
						break;
					case 3:
						$coef=1000;
						break;
					case 4:
						$coef=10000;
						break;
					case 5:
						$coef=100000;
						break;
					default:
					   $coef=0;
				}
				$date_tmp2=$date_tmp+(intval($after)*$coef);
			}
			else
				$date_tmp2=$date_tmp;
			if ($year1==NULL){
				$year1 = $date_tmp;
				$year2 = $date_tmp2;
			}else{
				if ($date_tmp<$year1)
					$year1 = $date_tmp;
				if ($date_tmp2>$year2)
					$year2 = $date_tmp2;
			}
		}
	}
}

if (($year1!=NULL)&&($year2==0))
		$year2=-1;
	
if (($year1==1)&&($precision<9))
		$year2=10*floor($year2/10);
if ($year1==NULL)
	$year1="NULL";
if ($year2==NULL)
	$year2="NULL";

$offic_url=$tab_prop["P856"];
if ($offic_url=="")
	$offic_url=$tab_prop["P973"];

$sql="INSERT INTO artworks (qwd,P18,hd,P214,P217,P347,P350,P373,P727,link,P1212,year1,year2,b_date,new_img) VALUES ($item,".$p18.",$hd,\"".$tab_prop["P214"]."\",\"".$tab_prop["P217"]."\",\"".$tab_prop["P347"]."\",\"".$tab_prop["P350"]."\",\"".$tab_prop["P373"]."\",\"".$tab_prop["P727"]."\",\"".$offic_url."\",\"".$tab_prop["P1212"]."\",$year1,$year2,\"".$b_date."\",$new_img)";
$rep=mysqli_query($link,$sql);

$sql="SELECT id FROM artworks WHERE qwd=\"$item\"";
$rep=mysqli_query($link,$sql);
$row = mysqli_fetch_assoc($rep);
$id_artwork=$row['id'];

// 1abels for artwork item
insert_label_page(1,$item,$id_artwork);

// Other properties
$tab_multi=array(31,135,136,144,170,179,180,186,195,276,361,921,941,1639);	
for ($i=0;$i<count($tab_multi);$i++){
	if ($claims["P".$tab_multi[$i]])
		foreach ($claims["P".$tab_multi[$i]] as $value){
			$val=intval($value["mainsnak"]["datavalue"]["value"]["numeric-id"]);
			$sql="SELECT id FROM p".$tab_multi[$i]." WHERE qwd=$val";
			$rep=mysqli_query($link,$sql);
			$newid="";
			$found=false;
			if (mysqli_num_rows($rep)==0){
				//Value of property inserted
				$p18_str=img_qwd($val);
				if ($p18_str!="")
					$p18=id_commons($p18_str);
				else
					$p18=0;
					
				$sql="INSERT INTO p".$tab_multi[$i]." (qwd,P18) VALUES ($val,".$p18.")";
				$rep=mysqli_query($link,$sql);
				
				$sql="SELECT id FROM p".$tab_multi[$i]." WHERE qwd=$val";
				$rep=mysqli_query($link,$sql);
				
				$row = mysqli_fetch_assoc($rep);
				$id_prop=$row['id'];
				$newid=$id_prop;
				//Labels of property inserted
				insert_label_page($tab_multi[$i],$val,$id_prop);
				
			}
			else{			
				$row = mysqli_fetch_assoc($rep);
				$id_prop=$row['id'];
				$found=true;	
			}
			$insertok=true;
			if (($tab_multi[$i]==195)||($tab_multi[$i]==276)){
				// Looking for uper-classes
				$sql="SELECT id,level FROM p".$tab_multi[$i]." WHERE qwd=$val";
				$rep=mysqli_query($link,$sql);

				$level=0;
				if (mysqli_num_rows($rep)>0){
					$row = mysqli_fetch_assoc($rep);
					$level=$row['level'];
				}
				if ((!$found)||($level!=0))
					parent_cherche($tab_multi[$i],$val,$id_artwork,$newid);
					
				$sql="SELECT id FROM artw_prop WHERE prop=".$tab_multi[$i]." and id_artw=$id_artwork and id_prop=$id_prop";
				$rep=mysqli_query($link,$sql);
				if (mysqli_num_rows($rep)!=0)
					$insertok=false;
			}
			
			if ($insertok){
				$sql="INSERT INTO artw_prop (prop,id_artw,id_prop) VALUES (".$tab_multi[$i].",$id_artwork,$id_prop)";
				$rep=mysqli_query($link,$sql);
			}
		}
	else
		if (!(($tab_multi[$i]=="31")||($tab_multi[$i]=="1639")))
			$tab_miss["m".$tab_multi[$i]]=1;
}

// missing props
$sql="UPDATE artworks SET ";
foreach($tab_miss as $key=>$value){
	if ($sql!="UPDATE artworks SET ")
		$sql.=",";
	if ((substr($key,0,1)=="m")&&($key!="mu"))
		$sql.=$key."=".$value;
	else
		$sql.="lb".$key."=".$value;
}
$sql.=" WHERE id=$id_artwork";
$rep=mysqli_query($link,$sql);
unset($tab_miss);

	}//it's a file
}//reading files in directory
mysqli_query($link,"ALTER TABLE commons_img DROP INDEX P18");
mysqli_close($link);
closedir($dir);

echo "\nCompilation done";
include $file_timer_end;
?>