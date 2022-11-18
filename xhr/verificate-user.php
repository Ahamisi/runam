<?php 

if ($f == 'verificate-user') {
    $data  = array(
        'status' => 304,
        'message' => ($error_icon . $wo['lang']['please_check_details'])
    );
    $error = false;
    if (!isset($_POST['name']) || !isset($_POST['text']) || !isset($_FILES['passport']) || !isset($_FILES['photo'])) {
        $error = true;
    } else {
        if (strlen($_POST['name']) < 3 || strlen($_POST['name']) > 50) {
            $error           = true;
            $data['message'] = $error_icon . $wo['lang']['username_characters_length'];
        }
        if (!file_exists($_FILES['passport']['tmp_name']) || !file_exists($_FILES['photo']['tmp_name'])) {
            $error           = true;
            $data['message'] = $error_icon . $wo['lang']['please_select_passport_id'];
        }
        if (file_exists($_FILES["passport"]["tmp_name"])) {
            $image = getimagesize($_FILES["passport"]["tmp_name"]);
            if (!in_array($image[2], array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP
            ))) {
                $error           = true;
                $data['message'] = $error_icon . $wo['lang']['passport_id_invalid'];
            }
        }
        if (file_exists($_FILES["photo"]["tmp_name"])) {
            $image = getimagesize($_FILES["photo"]["tmp_name"]);
            if (!in_array($image[2], array(
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_BMP
            ))) {
                $error           = true;
                $data['message'] = $error_icon . $wo['lang']['user_picture_invalid'];
            }
        }
    }
    if (!$error) {
        $registration_data = array(
            'user_id' => $wo['user']['id'],
            'message' => Wo_Secure($_POST['text']),
            'user_name' => Wo_Secure($_POST['name']),
            'passport' => '',
            'photo' => '',
            'type' => 'User',
            'seen' => 0,
            'doc_type' => Wo_Secure($_POST['document_type']),
        );
        $last_id           = Wo_SendVerificationRequest($registration_data);
        $myFileNames = array();
        
        if ($last_id && is_numeric($last_id)) {
            $files       = array(
                'passport' => $_FILES,
                'photo' => $_FILES
            );
            $update_data = array();
            foreach ($files as $key => $file) {
                $fileInfo          = array(
                    'file' => $file[$key]["tmp_name"],
                    'name' => $file[$key]['name'],
                    'size' => $file[$key]["size"],
                    'type' => $file[$key]["type"],
                    'types' => 'jpg,png,bmp,gif'
                );
                $media             = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $update_data[$key] = $media['filename'];
                }


                array_push($myFileNames,$media['filename']);

            }


            




            $doctype = $_POST['document_type'];
            $endpoint = "";
            $lastname = $_POST['name'];
            $id_number = $_POST['id_number'];


            // var_dump($doctype); die;


            if($doctype == 'dlicense'){
                $endpoint = "https://sandbox.myidentitypass.com/api/v2/biometrics/merchant/data/verification/drivers_license/image";
                // body => image
            }elseif($doctype == 'ipassport'){
                $endpoint = "https://sandbox.myidentitypass.com/api/v2/biometrics/merchant/data/verification/national_passport_with_face";
                // body => image
            }elseif($doctype == 'vcard'){
                $endpoint = "https://sandbox.myidentitypass.com/api/v2/biometrics/merchant/data/verification/voters_card/image";
                // body => image
            }elseif($doctype == 'nin'){
                $endpoint = "https://sandbox.myidentitypass.com/api/v2/biometrics/merchant/data/verification/nin/image";
                // body => image
            }

            $postdata = array(
                'image' => $myFileNames[0],
                'last_name' => $lastname,
                'number' => $id_number,
            );



            $ch = curl_init();


            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "image=".$postdata['image']."&last_name=".$postdata['last_name']."&number=".$postdata['number']);

            // curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            // curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);



            $headers = array(
                'x-api-key: test_ucc8c5fyl6rl78idn3lqjp:ogINip3R6hrzzARkTI42vv13ybY',
                'app-id:36c50c1c-801f-4bdc-bad8-b8aac80bcb60',
                'Content-Type: application/x-www-form-urlencoded',
            );

            // $headers[] = ;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }

            $api_result = json_encode($result);
            $dapires = json_decode($api_result);

            if($dapires){
                if (Wo_UpdateVerificationRequest($last_id, $update_data)) {
                    $data['status']  = 200;
                    // $data['message'] = $success_icon . $wo['lang']['verification_request_sent'];
                    $data['message'] = $success_icon . 'Verification Successful';

                    $data['url']     = $wo['config']['site_url'];
                }
            }



            curl_close($ch);


            




            
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
