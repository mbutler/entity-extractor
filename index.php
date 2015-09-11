<?php

function geocode($place) {
   $geourl = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($place).'&key=XXXXXXXXXXXXXXXXXXXXXXX';
   //echo $geourl;
  $georesponse = curl_get_contents($geourl);
  $locationdata = json_decode($georesponse);
  $coords = Array();
  $lat = $locationdata->results[0]->geometry->bounds->northeast->lat;
  $lng = $locationdata->results[0]->geometry->bounds->northeast->lng;

  $coords['lat'] = $lat;
  $coords['lng'] = $lng;

  return $coords;
}


function curl_get_contents($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}


?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Entity Extraction from the DIY History transcription API</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/small-business.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <h1>Historical Manuscipt Entity Extraction</h1>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container">

        <!-- Heading Row -->
        <div class="col-md-4">
            <form action="" method="post">
              DIYH file ID: <input name="diyhid" type="text" />  
              <input name="submit" type="submit" class="btn btn-success" />
            </form>                
        </div>
        <br />
        <?php

        if (isset($_POST['submit'])) {

          //need to update diy history file ids 92983 - 113959

          $fileid = $_POST['diyhid'];
          $diyh_file = 'diyhistory.lib.uiowa.edu/api/files/'.$fileid;
          $json = curl_get_contents($diyh_file);
          $obj = json_decode($json);
          ?>
        <div class="row">
            <div class="col-md-8">
                <img class="img-responsive img-rounded" src="<?php echo $obj->file_urls->fullsize; ?>" alt="">
            </div>
            <!-- /.col-md-8 -->
            
            <!-- /.col-md-4 -->
        </div>

          <?php

          switch ($fileid) {

            // this is so gross. Not all records output the same json so we have to detect ranges
            case ($fileid <= 76389):
              $cdmurl = $obj->element_texts[8]->text;
              $transcription = $obj->element_texts[10]->text;
              break;

            case ($fileid < 92983):
              $cdmurl = $obj->element_texts[8]->text;
              $transcription = $obj->element_texts[12]->text;
              break;

            case ($fileid < 113959):
              $cdmurl = $obj->element_texts[8]->text;
              $transcription = $obj->element_texts[3]->text;
              break;

            case ($fileid < 150000):
              $cdmurl = $obj->element_texts[2]->text;
              $transcription = $obj->element_texts[0]->text;
              break;

            default:
              $cdmurl = $obj->element_texts[2]->text;
              $transcription = $obj->element_texts[10]->text;
              break;

          }

          
          $cdmid = end(explode('/', $cdmurl));


          if ($obj->message) {
            echo "Not a valid record. Skipping";
          } else {
            //$transcription = $obj->element_texts[3]->text;
            echo "<br /><strong>DIYH record: " .$fileid. "</strong><br />";
            echo "<p>".$transcription."</p";


             $url = 'https://api.monkeylearn.com/v2/extractors/ex_isnnZRbS/extract/';
             $data = array('text_list' => array($transcription));
              
             $options = array(
                 'http' => array(
                     'header'  => "Content-type: application/json\r\n".
                         "Authorization:token XXXXXXXXXXXXXXXXXXXXXXXXXXXXX\r\n",
                     'method'  => 'POST',
                     'content' => json_encode($data),
                 ),
             );
             $context  = stream_context_create($options);
             $result = file_get_contents($url, false, $context);
             $entity = json_decode($result);

             $people = Array();
             $locations = Array();
             $orgs = Array();

             //iterate through each object and add entities to their own array
             foreach($entity->result[0] as $row)
             {
                 
               switch ($row->tag) {
                 case 'PERSON':
                   $people[] = $row->entity;
                   break;

                 case 'LOCATION':
                   $locations[] = $row->entity;
                   break;

                 case 'ORGANIZATION':
                   $orgs[] = $row->entity;
                   break;

               } 

             }


          };

        }


        ?>
        <!-- /.row -->

        <!-- /.row -->

        <!-- Content Row -->
        <div class="row">
            <div class="col-md-4">
                <h2>People</h2>
                <p><?php 
                    for($i=0; $i<count($people); $i++) {
                      echo "<h4>".$people[$i]."</h4><br />";
                    } 
                    ?></p>           
            </div>
            <!-- /.col-md-4 -->
            <div class="col-md-4">
                <h2>Places</h2>
                <p><?php 
                    for($i=0; $i<count($locations); $i++) {
                      $map = geocode($locations[$i]);                      
                      echo "<h4>".$locations[$i]." (".$map['lat']." ".$map['lng'].")</h4><br />";
                    } 
                    ?></p>                
            </div>
            <!-- /.col-md-4 -->
            <div class="col-md-4">
                <h2>Organizations</h2>
                <p><?php 
                    for($i=0; $i<count($orgs); $i++) {
                      echo "<h4>".$orgs[$i]."</h4><br />";
                    } 
                    ?></p>        
            </div>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->

        <!-- Footer -->
        <footer>
            <div class="row">
                <div class="col-lg-12">
                    <p>Copyright &copy; Digital Scholarship & Publishing Studio</p>
                </div>
            </div>
        </footer>

    </div>
    <!-- /.container -->

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

</body>

</html>







