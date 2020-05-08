(create)
//Mobile_api.php
function new_tournament() {
    $user_data = $this->check_token();
    $api_token = $this->input->get_request_header('API-TOKEN');
    $name = $this->input->post('name') ? $this->input->post('name') : null;
    $description = $this->input->post('description') ? $this->input->post('description') : null;
    $date = preg_match( self::date_format, $this->input->post('date')) ? $this->input->post('date') : null;
    $time = $this->input->post('tour_time');
    $registration_date = preg_match(self::date_format, $this->input->post('registration_date')) ? $this->input->post('registration_date') : null;
    $city = !preg_match(self::city_club_valid, $this->input->post('city'))? $this->input->post('city') : 'null';
    $address = $this->input->post('address') ? $this->input->post('address') : null;
    $geo = $this->input->post('geo') ? $this->input->post('geo') : null;
    $country_id = is_numeric($this->input->post('country_id')) ? $this->input->post('country_id') : null;
    $tournament_type = is_numeric($this->input->post('type')) ? $this->input->post('type') : null;
    if ($user_data && ($user_data->status == 'admin' || $user_data->status == 'master' || $user_data->status == 'demo-master')) {
        if (!($name && $city && $date && $time && $country_id && $tournament_type)) {
            echo json_encode(array(
                "message" => "input data insufficient",
                "name" => $name,
                "city" => $city,
                "date" => $date,
                "tour_time" => $time,
                "country_id" => $country_id,
                "type" => $tournament_type
            ));
            return;
        }
        $request = $this->api_model->new_tournament($name, $description, $date, $time, $registration_date,
            $city, $address, $geo, $country_id, $this->upload_photo(1, null, null, 3), $tournament_type, $api_token)->row();
        echo $request ?
            json_encode(array(
                'tournament added' => $request->id ? $request : false
            )) :
            json_encode(array('message' => 'database error'));
    } else {
        echo json_encode(array('message' => 'insufficient rights'));
    }
}
//Api_model.php
function new_tournament($name, $description, $date, $time,
                        $registration_date, $city, $address, $geo, $country_id,
                        $poster, $type, $organizer_token) {
    $this->db->reconnect();
    $date = $date == null ? 'null' : "'$date'";
    $time = $time == null ? 'null' : "'$time'";
    $registration_date = $registration_date == null ? 'null' : "'$registration_date'";
    return $query = $this->ci->db->query("call AddTournament('$name', '$description', $date, $time, $registration_date, 
                                                        '$city', '$address', '$geo', $country_id, '$poster', $type, '$organizer_token');");

}

(read)
//Mobile_api.php
function get_tournaments()
	{
		$tournament_id = is_numeric($this->input->get('tournament_id')) ? $this->input->get('tournament_id') : 'null';
		$user_data = $this->check_token();
		$user_id = $user_data ? $user_data->id : 'null';
		$country = is_numeric($this->input->get('country_id')) ? $this->input->get('country_id') : 'null';
		//1 => upcoming ; 2 => finished
		$tournament_type = is_numeric($this->input->get('type')) ? $this->input->get('type') : null;

		$result = (array)$this->api_model->get_tournaments($country, $user_id, $tournament_id);
		foreach ($result as $tournament) {
			!file_exists("." . $tournament->poster) ? $tournament->poster = "/images/tournament_banners/icon_tournament.png" : null;
		}

		$filteredResult = array();

		if (!$tournament_type) {
			echo json_encode($result);
		} elseif ($tournament_type == 1) {
			foreach ($result as $tournament) {
				$time = (string)$tournament->start_date;
				if (strtotime($time) > time()) {
					array_push($filteredResult, $tournament);
				}
			}
			echo json_encode($filteredResult);
		} elseif ($tournament_type == 2) {
			foreach ($result as $tournament) {
				$time = (string)$tournament->start_date;
				if (strtotime($time) <= time()) {
					array_push($filteredResult, $tournament);
				}
			}
			echo json_encode($filteredResult);
		} else {
			echo json_encode(array('invalid_type' => $tournament_type));
		}
	}
//Api_model.php
function get_tournaments($country, $user_id, $tournament_id)
	{
		$this->db->reconnect();
		return $this->ci->db->query("CALL `GetTournament`($tournament_id, $user_id, $country); ")->result();
	}

(update)
//Mobile_api.php
function update_tournament()
	{
		$user_data = $this->check_token();
		$tournament_id = is_numeric($this->input->post('tournament_id')) ? $this->input->post('tournament_id') : null;
		$tournament_name = $this->input->post('name') ? $this->input->post('name') : 'null';
		$description = $this->input->post('description') ? $this->input->post('description') : 'null';
		$date = $this->input->post('date') ? date('Y-m-d',strtotime($this->input->post('date'))) : "null";
		$time = $this->input->post('tour_time') ? $this->input->post('tour_time') : null;
		$registration_date = $this->input->post('registration_date') ? date('Y-m-d',strtotime($this->input->post('registration_date'))): 'null';
		$city = !preg_match(self::city_club_valid, $this->input->post('city'))? $this->input->post('city') : 'null';
		$address = $this->input->post('address') ? $this->input->post('address') : 'null';
		$geo = $this->input->post('geo') ? $this->input->post('geo') : 'null';
		$country_id = is_numeric($this->input->post('country_id')) ? $this->input->post('country_id') : 'null';
		$type = is_numeric($this->input->post('type')) ? $this->input->post('type') : 'null';
		$api_token = $this->input->get_request_header('API-TOKEN');
		$banner = $this->upload_photo($tournament_id, null, null, 3);
		if (!$tournament_id) echo json_encode(array('msg' => 'tournament id missing'));
		elseif ( $user_data && ( $user_data->status == 'admin' || $user_data->status == 'master' || $user_data->status == 'demo-master' ) ) {
				$result = $this->api_model->update_tournament(	$tournament_id, $tournament_name, $description,
																$date, $time, $registration_date,
																$city, $address, $geo, $country_id,
																$banner, $type, $api_token );
				$update_status = $result->tournament_update ? "updated" : "could not be found";
				echo json_encode(array('msg' => 'tournament ' . $update_status));
		} else {
			echo json_encode(array('msg' => 'insufficient rights'));
		}
	}
//Api_model.php
function update_tournament($tournament_id, $tournament_name, $description,
							   $date, $time, $registration_date,
							   $city, $address, $geo, $country_id,
							   $poster, $type, $api_token)
	{
		$this->db->reconnect();
		$tournament_name = $tournament_name == 'null' ? $tournament_name : "'$tournament_name'";
		$description = $description == 'null' ? $description : "'$description'";
		$city = $city == 'null' ? $city : "'$city'";
		$address = $address == 'null' ? $address : "'$address'";
		$geo = $geo == 'null' ? $geo : "'$geo'";
		$poster = $poster == 'null' ? $poster : "'$poster'";
		$api_token = $api_token == 'null' ? $api_token : "'$api_token'";
		$date = $date == 'null' ? 'null' : "'$date'";
		$time = $time == null ? 'null' : "'$time'";
		$registration_date = $registration_date == 'null' ? 'null' : "'$registration_date'";
		return  $this->ci->db->query("call UpdateTournament($tournament_id,$tournament_name,
                                                                $description, $date, $time,
                                                                $registration_date, $city,
                                                                $address, $geo, $country_id,
                                                                $poster, $type, $api_token);")->row();

	}
(delete)
//Mobile_api.php
function remove_certificate(){
		$user_data = $this->check_token();
		$certificate_id = is_numeric($this->input->get('certificate_id')) ? $this->input->get('certificate_id') : null;
		if($certificate_id && $user_data && $this->api_model->is_certificate_owner($certificate_id,$user_data->id)->is_owner){
			$previous_pictures = $this->api_model->get_previous_picture($certificate_id, "null", "null", 4) ?? null;
			$upload_path = './images/tournament_certificates/';
				if ($previous_pictures[0] && $previous_pictures[0]->picture != $upload_path
					&& file_exists($previous_pictures[0]->picture)){
					unlink($previous_pictures[0]->picture);
				}
			echo $this->api_model->remove_certificate($certificate_id, $user_data->id)->removed?
				json_encode(array("msg" => "Certificate removed")):
				json_encode(array("msg"=>"Certificate removal failed"));
		} else {
			echo json_encode(array("msg" => "Insufficient data/ not tournament owner"));
		}
	}
//Api_model.php
function remove_certificate($certificate_id, $user_id){
		$this->db->reconnect();
		return $this->ci->db->query("CALL RemoveCertificate($certificate_id, $user_id)")->row();
	}
