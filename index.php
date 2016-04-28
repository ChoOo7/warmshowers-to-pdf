<?php

$started = false;

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
  $email = $_REQUEST['email'];
  $password = $_REQUEST['password'];
  $reverse = @$_REQUEST['reverse'];
  $fromKm = $_REQUEST['fromKm'];
  $toKm = $_REQUEST['toKm'];

  $fileName = $_FILES['gpxFile']['name'];
  if(strpos($fileName, '.php') !== false || strpos($fileName, '.gpx') === false)
  {
    echo "ERROR";
    die();
  }

  $projectName = str_replace('.gpx', '', basename($fileName)).'-'.date('d-m-Y-H-i-s');

  $gpxDest = __DIR__.'/generated/'.$projectName.'.gpx';

  move_uploaded_file($_FILES['gpxFile']['tmp_name'], $gpxDest);

  $command = "php ".__DIR__.'/cli.php '.escapeshellarg($email).' '.escapeshellarg($password).' '.escapeshellarg($gpxDest).' "" '.escapeshellarg($fromKm).' '.escapeshellarg($toKm).' '.escapeshellarg($reverse).' '.escapeshellarg($_SERVER['SERVER_NAME']);
  $command ='nohup '.$command.' & ';
  echo $command;
  exec($command);
  $started = true;
}


?>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Warmshowers export</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
  </head>
  <body>
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <h2>
          Warmshowers hosts export from GPX file
        </h2>
        <p>
          Allow to create an epub file from a GPX file
        </p>
        <form role="form" method="POST" action="/" enctype="multipart/form-data">

          <div class="form-group">
            <label for="email">
              Your Warmshowers email
            </label>
            <input type="email" class="form-control" id="email" name="email"/>
          </div>

          <div class="form-group">
            <label for="password">
              Your Warmshower password
            </label>
            <input type="password" class="form-control" id="password" name="password"/>
          </div>

          <div class="form-group">
            <label for="gpxFile">
              Select you GPX file
            </label>
            <input type="file" id="gpxFile" name="gpxFile"/>
            <p class="help-block">
              Select your GPX file on your computer
            </p>
          </div>

          <div class="checkbox">
            <label>
              <input type="checkbox" name="reverse" value="1" /> Reverse order
             </label>
            <p class="help-block">
              If checked, hosts will be presented in the reverse order of the GPX File
            </p>
          </div>

          <div class="form-group">
            <label for="fromKm">
              From KM (Optional)
            </label>
            <input type="text" class="form-control" id="fromKm" name="fromKm"/>
          </div>

          <div class="form-group">
            <label for="toKm">
              To KM (Optional)
            </label>
            <input type="text" class="form-control" id="toKm" name="toKm"/>
          </div>

          <button type="submit" class="btn btn-default">
            Start the generation process
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php if($started): ?>
  <script>
    alert("Generation lancée. Vous recevrez le resultat par email");
  </script>
  <?php endif; ?>

</body>
</html>
