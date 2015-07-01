<?
if(function_exists("getImageSizeFromString")) {
echo "1";
} else {
  echo "0";
}
exit;

$picture = get_reply("select memberPicture from memberPictures where memberNumber='{$_GET['id']}' order by dateOfPicture DESC limit 1");

$picture = ($picture ? $picture : file_get_contents(getcwd()."/images/no_photo.jpg"));

$headers = getImageSizeFromString($picture);
header("Content-type:".$headers['type']);
echo $picture;
exit();

?>