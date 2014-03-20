<?php
//pool documentation:	http://eligius.st/~gateway/pool-apis

class eligius {

	public function __construct($username=false){
		$this->username = $username;
		$this->base = 'http://eligius.st/~wizkid057/newstats/';
		$this->base_historic = 'http://eligius.st/~luke-jr/raw/7/';
	}

	public function fetch($url,$json_decode=true){
		$ch = curl_init(); 
        	curl_setopt($ch, CURLOPT_URL, $url); 
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        	$data = curl_exec($ch); 
        	curl_close($ch);    
        	return ($json_decode) ? json_decode($data) : $data;		
	}

	//--api based requests
		public function api_request($cmd,$param=false,$user=true){
			if($this->username){
				$api_link = $this->base.'api.php?cmd='.$cmd;
				if($user) $api_link .= '&username='.$this->username;

				if($param && !empty($param))
					foreach($param as $key=>$val) $api_link .= '&'.$key.'='.$val;

				$data = $this->fetch($api_link,true);
				if(!empty($data) && isset($data)) return $data;
			}
		}

		public function get_user_payout(){
			return $this->api_request('getuserstat');
		}

		public function get_user_hashrate(){
			return $this->api_request('gethashrate');
		}	

		public function get_blocks($limit=15){
			return $this->api_request('getblocks',array('limit'=>$limit));
		}

		public function get_user_accepted(){
			return $this->api_request('getacceptedcount');
		}

		public function get_pool_hashrate(){
			return $this->api_request('gethashrate',false,false);	
		}
	//==api based requests

	//--historic based requests
		public function get_payout_queue(){
			$data = $this->fetch($this->base_historic.'payout_queue.txt',false);
			return (!empty($data)) ? $data : false;
		}

		public function get_pool_balances(){
			//this is a large file, requests may time out depending on server settings
			//sometimes this will bail out because the file is being updated on the server
			$data = $this->fetch($this->base_historic.'balances.json');
			return (!empty($data)) ? $data : false;
		}

		public function get_user_que(){
			$data = new stdClass();
			$data->empty = true;
			$payout = $this->get_payout_queue();
			$balance = $this->get_pool_balances();

			if(!$payout || !$balance) return $data;

			$que_position = 1;
			$blocks = $total_all = $total = $tbc = $tbcc = 0;

			foreach($payout as $key){
				if(!empty($balance->{$key})){
					$value = $balance->{$key};
					if ($value->balance<1048576){ 
						$tbc += $value->balance;
						$tbcc++;
						$value->balance = 0; 
					}

					while($value->balance>0){
						if($key==$this->username){
							return (object) array(
								'position'	=> $que_position,
								'blocks'	=> $blocks,
								'amount'	=> $value->balance*0.00000001,
								'age'		=> $this->clean_time(time()-$value->oldest)
							);
						}
						if($total+$value->balance>2500000000){
							$maxbal = 2500000000-$total;
							$value->balance -= $maxbal;
							$total_all += $maxbal;
							$total = 0; 
							$blocks++;
							$que_position++;
						} else {
							$total+=$value->balance;
							$total_all+=$value->balance;
							$value->balance = 0;
							$que_position++;
						}
					}
				}
			}
			return $data;
		}
	//==historic based requests

	//--variety requests
		public function get_live_data(){
			$data = $this->fetch($this->base.'instant.php/livedata.json',true);
			if(!empty($data) && isset($data)) return $data;
		}

		public function get_worker_hashrates(){
			$data = $this->fetch($this->base.'userstats.php/'.$this->username.'?cmd=hashgraph',false);
			$row = array_reverse(explode("\n",$data));

			$worker = new stdClass();
			$worker->data = array();
			$worker->labels = explode(',',$row[(count($row)-1)]);
			unset($worker->labels[0]);
			for($x=0; $x<3; $x++) array_pop($worker->labels);
			foreach($row as $item){
				$col = explode(',',$item);
				$valid = true;
				for($x=0; $x<=count($worker->labels); $x++)
					if(empty($col[$x]) || $col[$x]=='') $valid = false;
				if($valid){
					unset($col[0]);
					for($x=1; $x<=count($worker->labels); $x++)
						$worker->data[round($col[$x],0)] = (object) array('label'=>$worker->labels[$x],'value'=>$col[$x]);
					break;
				}
			}
			krsort($worker->data);
			return (!empty($worker->data) && count($worker->data)>0) ? $worker->data : null;
		}

		public function get_user_payment_history($limit=false){
			$data = $this->fetch($this->base.'userstats.php/'.$this->username.'?cmd=balancegraph&start=0&back=604800',false);
			$row = array_reverse(explode("\n",$data));
			$data = $payment = $result = array();

			foreach($row as $item) if(!empty($item)) $data[] = explode(',',$item);
			for($x=0; $x<count($data)-1; $x++)
				if($data[$x][1]!=$data[($x+1)][1])
					$payment[] = $x;

			foreach($payment as $item){
				$amount = $data[$item][1]-$data[($item+1)][1];
				if($amount>0) $result[] = (object) array('amount'=>$amount,'date'=>strtotime($data[$item][0]));
			}

			if($limit) $result = array_slice($result,0,($limit));
			return $result;
		}
	//==variety requests

	//--formatting functions
		public function calc_cdf($difficulty,$accepted){
			//bitminter format
		    return number_format((1-pow(1-(1/$difficulty),$accepted))*100,2,'.','');
		}

		public function clean_time($item){
			//hours:minutes:seconds
			return sprintf('%02d:%02d:%02d',($item/3600),($item/60%60),$item%60);
		}
	//==formatting functions

}
?>
