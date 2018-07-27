<?php

class Colors {
		private $foreground_colors = array();
		private $background_colors = array();

		public function __construct() {
			// Set up shell colors
			$this->foreground_colors['black'] = '0;30';
			$this->foreground_colors['dark_gray'] = '1;30';
			$this->foreground_colors['blue'] = '0;34';
			$this->foreground_colors['light_blue'] = '1;34';
			$this->foreground_colors['green'] = '0;32';
			$this->foreground_colors['light_green'] = '1;32';
			$this->foreground_colors['cyan'] = '0;36';
			$this->foreground_colors['light_cyan'] = '1;36';
			$this->foreground_colors['red'] = '0;31';
			$this->foreground_colors['light_red'] = '1;31';
			$this->foreground_colors['purple'] = '0;35';
			$this->foreground_colors['light_purple'] = '1;35';
			$this->foreground_colors['brown'] = '0;33';
			$this->foreground_colors['yellow'] = '1;33';
			$this->foreground_colors['light_gray'] = '0;37';
			$this->foreground_colors['white'] = '1;37';

			$this->background_colors['black'] = '40';
			$this->background_colors['red'] = '41';
			$this->background_colors['green'] = '42';
			$this->background_colors['yellow'] = '43';
			$this->background_colors['blue'] = '44';
			$this->background_colors['magenta'] = '45';
			$this->background_colors['cyan'] = '46';
			$this->background_colors['light_gray'] = '47';
		}

		// Returns colored string
		public function getColoredString($string, $foreground_color = null, $background_color = null) {
			$colored_string = "";

			// Check if given foreground color found
			if (isset($this->foreground_colors[$foreground_color])) {
				$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
			}
			// Check if given background color found
			if (isset($this->background_colors[$background_color])) {
				$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
			}

			// Add string and end coloring
			$colored_string .=  $string . "\033[0m";

			return $colored_string;
		}

		// Returns all foreground color names
		public function getForegroundColors() {
			return array_keys($this->foreground_colors);
		}

		// Returns all background color names
		public function getBackgroundColors() {
			return array_keys($this->background_colors);
		}
	}


Class Nginx {
	protected $request;
	protected $hos;
	protected $path;

	protected $conf_path 	= "/usr/local/etc/nginx/sites-enabled/";
	protected $hosts_path	= "/private/etc/hosts";
	protected $php_fpm		= "127.0.0.1:9000";

	public function checkAndSetParameters()
	{
		global $argv;
		global $argc;

		$colors = new Colors();
		if($argc < 2){
			die($colors->getColoredString("You must tell me what do you want to do ",'red','white')."\n");
		}

		if($argc < 3){
				die($colors->getColoredString("You must tell me the vHost ",'red','white')."\n");
		}

		if(!in_array(strtolower($argv[1]), ['create','delete'])){
			die($colors->getColoredString("I can only understand create and delete orders",'red','white')."\n");
		}

		if($argv[1] == "create"){
			if($argc < 4){
				die($colors->getColoredString("You must tell me where is your project",'red','white')."\n");
			}
		}
		
		$this->request 	= $argv[1];
		$this->host 	= $argv[2];
		if($argv[1] == "create"){
			$this->path 	= '/'.trim($argv[3],'/');
		}
	}

	public function proccess()
	{
		$this->checkAndSetParameters();
		if($this->request == 'create'){
			$this->create();
		}elseif($this->request == 'delete'){
			$this->delete();
		}
	}

	public function create()
	{
		$colors = new Colors();
		if ( file_exists($this->conf_path.$this->host) ) {
			return $colors->getColoredString("Host Already Exist",'red','white')."\n";
		}

		file_put_contents($this->conf_path.$this->host,$this->template());
		echo $colors->getColoredString("Created virtual Host File ",'green','white')."\n";
		$hosts_str =  '127.0.0.1	'.$this->host;
		file_put_contents($this->hosts_path, $hosts_str.PHP_EOL , FILE_APPEND | LOCK_EX);
		echo $colors->getColoredString("Hosts File Modeifed",'green','white')."\n";
		exec("nginx -sreload");
		echo $colors->getColoredString("Nginx Reloeaded",'green','white')."\n";

	}

	public function delete()
	{
		$colors = new Colors();
		$hostFile = $this->conf_path.$this->host;
		if ( file_exists($hostFile) ) {
			unlink($hostFile);
		}
		echo $colors->getColoredString("Deleted virtual Host File ",'green','white')."\n";

		$this->deleteLineFromFile($hostFile,$this->host);
		echo $colors->getColoredString("Deleted Hosts File Line",'green','white')."\n";
		
		exec("nginx -sreload");
		echo $colors->getColoredString("Nginx Restarted",'green','white')."\n";
		//restart nginx
		
	}

	public function deleteLineFromFile($file,$replaceItem)
	{
		$newContents = "";
		// Check to see if the file exists
		if ( file_exists($file) ) {
		    // Wrap our IO stuff so we catch any exceptions
		    try {
		        // Open the file for reading
		        $fp = fopen($file, "r");
		        if ($fp) {
		            // Loop line-by-line through the file
		            while($line = fgets($fp, 4096) !== false) {

		                // Only add the line if it doesn't contain $replaceItem
		                // This is case insensitive. I.E. 'item' == 'ITEM'
		                // For case sensitive, use strstr()
		                if ( stristr($line, $replaceItem) == false ) {
		                    $newContents .= $line;
		                }
		            }
		        }
		        // Close our file
		        fclose($fp);
		        // Replace the contents of the file with the new contents
		        file_put_contents($file, $newContents);                
		    } catch (Exception $e) {
		        throw new Exception($e->getMessage());
		    }
		}
	}

	public function template()
	{
		$template = 'server{
    listen 80;
    root '.$this->path.'/public;
    index index.php index.html index.htm;
    server_name '.$this->host.';

    location / {
            autoindex on;
            try_files   $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass '.$this->php_fpm.';
        fastcgi_index index.php;
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        include fastcgi_params;
    }
}
';
		return $template;
	}
}

$uid = posix_getuid();
if($uid != 0){
	$colors = new Colors();
	die($colors->getColoredString("You must run this script as root",'red','white')."\n");
}
$nginx = new Nginx();
echo $nginx->proccess();
?>










