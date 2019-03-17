<?php
    class ETHAPI {
        private $host = "192.168.1.64";
        private $relays = array();

        public function __construct($host){
            $this->host = $host; 
            $this->getRelayStatus(); 
        }

        private function performGET($path, $query = array()){
            $ch = curl_init(); 

            $qs = ""; 
            if (sizeof($query) > 0){
                $qs .= "?";
                $params = array(); 
                foreach ($query as $param => $value){
                    $params[] = $param."=".urlencode($value);
                }
                $qs .= implode("&", $params);
            }

            curl_setopt($ch, CURLOPT_URL, "http://".$this->host."/".$path.$qs);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "admin:password");

            $data = curl_exec($ch);

            curl_close($ch); 

            return $data; 
        }

        private function getRelayStatus(){
            $xml = $this->performGET("status.xml");

            if (!$xml) return false; 

            $doc = new DOMDocument(); 
            $doc->loadXML($xml); 
            $response = $doc->getElementsByTagName("response")->item(0); 
            for ($r = 0; $r < 8; $r++){
                $status = $response->getElementsByTagName("relay".$r)->item(0)->textContent; 
                $this->relays[$r] = $status; 
            }
            return $this->relays; 
        }

        public function getStatus($relais){
            $this->getRelayStatus();
            if (isset($this->relays[$relais])) return $this->relays[$relais];
            return false; 
        }

        public function switch($relais, $state = null){
            $switchNeeded = true; 

            $this->getRelayStatus(); 
            if ($state == true && $this->relays[$relais]) $switchNeeded = false; 
            if ($state == false && !$this->relays[$relais]) $switchNeeded = false; 

            if ($switchNeeded) $this->performGET("io.cgi", array("relay" => $relais));
        }

        public function impulse($relais, $time = 400000){
            // on
            $this->switch($relais, true); 

            // time: 2000000 = 2s
            usleep($time); 

            // off
            $this->switch($relais, false); 

        }
    }
?>