<?php
    require_once 'vendor/autoload.php';
    require_once 'defines/CDH_awsDefinesAll.php';
    #require_once 'classes/CDH_awsRecordSet.php';

    /*
    * este fichero contiene los datos con la configuracion propia de cada CMS
    * si no encuentra los ficheros de los cms inluie el fichero con los datos por defecto:
    * defines/CDH_Datos_por_defecto.php
    */
    require_once 'defines/CDH_CMS_config.php';

    use Aws\S3\S3Client;
    use Aws\Exception\AwsException;


    /**
     * Nombre de dominio con punto tipo [nombreDeDominio].[es|com|org|...]
     * Nombre de dominio sin punto tipo [nombreDeDominio]
     * Nombre de dominio abreviado(5 letras) [nddom]
    
     * Nmbre del CMS prestashop|wordpress|etc
     * Versión del CMS tipo "1.6.1.4"

     * ssh - login de acceso al servidor local
     * ssh - passwd de acceso al servidor local
     * ssh - path absoluto al directorio web

     * ip ó nombre del servidor de la DB. En este caso hemos usado "vl19193.dinaserver.com"
     * Nombre de la base de datos
     * Login de acceso a la base de datos
     * Passwd de acceso a la base de datos

     * Region: Para Europa usaremos la región eu-west-1
     *       (Consultar otros paises, continentes y lugares)

    
     * 
     */

    class CDH_PS_userDataChecker{

        private $domainWithDot;
        private $domainWithoutDot; 
        //datos config
        private $cmsData;


        public function __construct(){
            
            $this->setCmsData();
            $this->setDomainWithDot();
            $this->setDomainWithoutDot();
        }

        /*
        * crear array con los datos del cms
        * buscar los ficheros config de cada cms
        * si no existe usar el fichero por defecto en /../defines/CDH_Datos_por_defecto.php
        */
        public function setCmsData(){
            //buscar los ficheros config de cada cms
            
            //buscar el fichero config
            if (file_exists(__DIR__ . '/../../CDH_PS_config.php')) {
                include_once __DIR__ . '/../../CDH_PS_config.php';

                $this->cmsData = $CDH_Config;
            }else{
                //usar el fichero por defecto si no se encuentran los ficheros de un cms especifico
                include_once 'defines/CDH_Datos_por_defecto.php';
                $this->cmsData = $CDH_Config;
            } 

        }

        public function getDBName(){
            return $this->cmsData['cdh_bbdd_name_cloud'];
        }

        public function getDBMasterUsername(){
            return $this->cmsData['cdh_bbdd_user_cloud'];
        }

        public function getDBMasterUserPassword(){
           return $this->cmsData['cdh_bbdd_passwd_cloud'];
        }

        public function getDBMasterServer(){
           return $this->cmsData['cdh_bbbb_server'];
        }

        public function getCmsName(){
            //var_dump($this->cmsData);die();
            return $this->cmsData['nombre_cms'];
        }

        public function getCmsBaseDir(){
            return $this->cmsData['cms_root_folder'];
        }


        public function getCmsVersion() {
            return $this->cmsData['version_cms'];
        }


        public function getPhpVersion() {
            $phpversion = substr(phpversion(), 0, 3);
            return $phpversion; //'7.4';
        }

        public function getAwsProfile() {
            return $this->cmsData['aws_profile'];

        }

        public function getAwsAccessKeyId() {
            return $this->cmsData['aws_access_key_id'];
        }

        public function getAwsSecretAccessKey() {
            return $this->cmsData['aws_secret_access_key'];
        }

        public function getApacheUser() {
            return 'www-data';
        }

        public function getApacheGroup() {
            return 'www-data';
        }

        
        public function getDirEfs(){
            return '/var/www/cms';

        }



        /**
         * ¡¡¡¡¡¡¡¡¡ OJO !!!!!!!!!! 
         * 
         * Completar clase y modificar constantes
         * 
         */

        private function setDomainWithDot() {
            $this->domainWithDot = $this->cmsData['cdh_dominio_con_punto'];

        }


        private function setDomainWithoutDot(){
            $this->domainWithoutDot = $this->cmsData['cdh_dominio_sin_punto'];

        }


        public function getDomainWithDot(){
            return $this->domainWithDot;
        }

        public function getDomainWithoutDot(){
            return $this->domainWithoutDot;
        }

        /**
         * recuperar el dominio
         * @return string nombre de dominio se obtiene desde la api de Prestashop
         
            *public function getDomainName(){
            *return $this->getDomainWithDot();
        *}*/

        /**
         * recupera el dominio sin el punto y terminacion
         * @return string nombre de dominio sin la terminacion
         
        *public function setDomaineNoDot(){
        *    $domaine = $this->dommaineName();
    
        *    $domaine_no_dot =  strpos($domaine, '.') !== false ? 
        *                        substr($domaine, 0, strpos($domaine, '.')) :
        *                        $domaine;

        *    return $domaine_no_dot;
        *}*/


        /**
         * @param string $accessKey Dato que se obtiene a través del servicio IAM de AWS
         * @param string $secretAccessKey Dato que se obtiene a través del servicio IAM de AWS
         * @return boolean true si es correcta la combinación de $accessKey y $secretAccessKey
         * @return boolean false si no es correcta la combinación de $accessKey y $secretAccessKey 
         */

        public function checkAwsCredentials( $user, $accessKey, $secretAccessKey ){
            try {
                $s3Client = new S3Client([
                    'version'     => 'latest',
                    'region'      => 'eu-west-1',
                    'credentials' => [
                        'key'    => CLOUDATOR_AWS_ACCESS_KEY,
                        'secret' => CLOUDATOR_AWS_SECRET_ACCESS_KEY,
                    ]
                ]);
                $result = $s3Client->listBuckets();
            
                var_dump($result);
                return true;
            } catch (AwsException $e) {
                  $err_msq = $e->getAwsErrorMessage();
                  if ($err_msq) {
                    /*
                    * codigo manejo de error
                    */
                  } 
                  return false;
            }

        }


        /**
         * @param string $host Una ip o nombre de dominio
         * @param string $user Nombre de ususario del servicio
         * @param string $password clave de acceso al sistema
         * @return boolean true si es correcta la combinación de $host, $user, $password
         * @return boolean false si no es correcta la combinación de $host, $user, $password 
         */
        /**
         * 
         * public function checkSshCredentials( $host, $user, $password ){
         *       $connection = ssh2_connect($host, 22);
         *       if(ssh2_auth_password($connection, $user, $password)){
         *           return true;
         *       }
         *       else{
         *           return false;
         *       }
         *   } 
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         */
        

        /**
         * @param string $host          Una ip o nombre de dominio
         * @param string $databaseName  Nombre de la base de datos
         * @param string $user          Nombre de ususario del servicio
         * @param string $password      Clave de acceso al sistema
         * @return boolean true         Si es correcta la combinación de $host, $user, $password
         * @return boolean false        Si no es correcta la combinación de $host, $user, $password 
         */

        /*    
        public function checkDatabaseCredentials( $host, $databaseName, $user, $password ){
            $no_err = true;
            //prueba crear un objeto PDO, si PDO lanza un error $no_err = false
            try{
                $con = new PDO("mysql:host=$db_server;dbname=$db_name", $db_user, $db_pass);
                $con = null;
            }catch (PDOException $e){
                $no_err = false;
            }
            
            //devolver error: false no se puede conectar, true conexion ok
            return $no_err;
        }
        */

        /**
         * encriptar los datos
         */
        function encryptDecrypt($action, $string, $key) 
        {
            
            if ($key) {
                $output = false;

                $encrypt_method = "AES-256-CBC";
                $secret_iv = 'mastextoparajoderalhacker';
                
                $iv = substr(hash('sha256', $secret_iv), 0, 16);

                if ( $action == 'encrypt' ) {
                    $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                    $output = base64_encode($output);
                } else if( $action == 'decrypt' ) {
                    $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
                }

                return $output;
            }else{
                return false;
            }
            
        }

    /**
	 * procesar fichero csv
     * 
	 */
	public static function csvParser($fichero){
		//array para guardar cada linea del fichero csv
		$datos_fichero = array();

		//abrir fichero, leer una linea a la ves y volcar en datos_fichero, ignorar lineas vacias
		$fichero_csv = fopen($fichero, "r");
		while(!feof($fichero)){
			$datos_linea = fgets($fichero);
			if(!empty($datos_linea)){
				$datos_fichero[] = $datos_linea;
			}
		}
		fclose($fichero_csv);

		//devolver array con datos del fichero csv
		return $datos_fichero;
	}
    /*
    public function createRecords( $cvsFile   ) {
            if ( $listado = file( $cvsFile ) == false )
                return false;

            #Buscamos DNS Principal


            $types_allowed = array(
                'A', 'AAAA', 'AFSDB', 'APL', 'CAA', 'CDNSKEY', 'CDS','CERT','CNAME','DHCID','DLV','DNAME',
                'DNSKEY','DS','IPSECKEY','KEY','KX','LOC','MX','RFC','NAPTR','NS','NSEC','NSEC3','NSEC3PARAM',
                'PTR','RRSIG','RP','SIG','SOA','SPFi','SRV','SSHFP','TA','TKEY','TLSA','TSIG','TXT','URI','*', 
                'AXFR','IXFR','OPT'
            );


            for( $i=0; $i < count( $listado ); $i++  ) {
                $rs = explode( ',', $listado[$i] );
                if ( $rs[1] == '@' ){
                    $types_allowed[ 'main' ] = $rs;
                    continue;
                }
                $types_allowed[ $rs[0] ] = $rs;             
            }

            foreach ( $types_allowed as $type ) {
                if ( $type[1] == '@' )
                    $type_principal[] = $type;
            }
            


            foreach (  $listado as $rs  ) {
                $rs = explode( ',', $rs );
                if( $rs[1] == '@' )
                    $encontrados_at++;

            }


    }
    */
    public function setClientR53(){
            $this->clientR53 = new Aws\Route53\Route53Client([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE
            ]);
    }

    public function getClientR53(){
            return $this->clientR53;    
    }

        
    }


?>
