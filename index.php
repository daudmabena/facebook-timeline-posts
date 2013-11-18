<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>FacebookWall Test</title>
        <link rel='stylesheet' href='css/normalize.css'>
        <link rel='stylesheet' href='css/styles.css'>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script src="js/scripts.js"></script>
    </head>
    <body>
        <?php
            require_once('FacebookWall.php');

            $id = 'facebook';
            $token = '326204564096805|TJBwx3q1wcOj62mPmN3K743K0us';

            $fb = new FacebookWall($id, $token);
            echo $fb->render();
        ?>
    </body>
</html>