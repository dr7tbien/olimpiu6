<?php

    /**
    * Just another function to inherit.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0
    * @abstract
    *
    **/


    require_once 'defines/CDH_awsDefinesAll.php';
    require_once 'defines/CDH_definesErrorMessages_ES.php';
    require_once 'vendor/autoload.php';

    require_once 'classes/CDH_Logger.php';
    
    #require_once 'defines/coder.php';
    
    //require_once 'defines/CDH_PS_awsDefineAll.php';
    use Aws\Exception\AwsException;
    #use Aws\Credentials\Credentials;
    use Aws\Credentials\CredentialProvider;

    abstract class CDH_abstractAwsService{

        private $cloudObject;

        private $credentials;
        private $id;

        private $language = "ES";

        private $clientAcm;
        private $clientAlb;
        private $clientAsc;
        private $clientCwt;
        private $clientDsy;
        private $clientEc2;
        private $clientEcc;
        private $clientEfs;
        private $clientIam;
        private $clientLb2;
        private $clientR53;
        private $clientRds;
        private $clientRec;
        private $clientS3;

        private $className;


        #ßabstract public function __construct();
        abstract public function create              ();
        abstract public function waitToBeCreated     ($param);
        

        public function setCredentials(){
            $this->credentials = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
        }

        public function getCredentials(){
            return $this->credentials;
        }

        /*
        * recuperar el nombre de cada clase
        */
        public function setClassName($className){
            $this->className = $className;
        }

        public function getClassName(){
            return $this->className;
        }


        public function setClientAcm( $region = false ){
            
            if( $region == false ) 
                $region = CDH_AWS_REGION;    

            $this->clientAcm = new Aws\Acm\AcmClient ([
                #'region'    => CDH_AWS_REGION,
                'region'    => $region,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientAcm(){
            return $this->clientAcm;
        }

        public  function setClientAlb(){
            #$credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
            $this->clientAlb = new  Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client ([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public  function getClientAlb() {
            return $this->clientAlb;
        }

        public function setClientAsc(){
            #$credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
            $this->clientAsc = new Aws\AutoScaling\AutoScalingClient([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }
    
        public function getClientAsc(){
            return $this->clientAsc;
        }

        public function setClientCwt(){
            #$credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
            $this->clientCwt = new Aws\CloudWatch\CloudWatchClient ([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientCwt(){
            return $this->clientCwt;
        }

        public function setClientDsy(){
            $this->clientDsy = new Aws\DataSync\DataSyncClient([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientDsy(){
            return $this->clientDsy;
        }

        public function setClientEc2(){

            #$provider = CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH);
		    #$provider = CredentialProvider::memoize($provider);
            #$provider = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );

            $this->clientEc2 = new Aws\Ec2\Ec2Client([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientEc2(){
            return $this->clientEc2;
            #return $this->clientEc2;
        }

        public function setClientEcc(){
            #$provider = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientEcc = new Aws\ElastiCache\ElastiCacheClient([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public function getClientEcc(){
            return $this->clientEcc;
        }

        public function setClientEfs() {
            #$provider = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientEfs = new Aws\Efs\EfsClient ([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public function getClientEfs(){
            return $this->clientEfs;
        }

        public function setClientIam(){
            #$provider = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientIam = new Aws\Iam\IamClient([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientIam(){
            return $this->clientIam;
        }

        public  function setClientLb2(){
            #$provider = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientLb2 = new  Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client ([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
            
    
        }
    
        public  function getClientLb2() {
            return $this->clientLb2;
        }

        public function setClientR53(){
            #$provider = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientR53 = new Aws\Route53\Route53Client([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientR53(){
            return $this->clientR53;
        }

        public function setClientRds(){
            #$provider       = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientRds = new Aws\Rds\RdsClient([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientRds(){
            return $this->clientRds;
        }

        public function setClientRec(){
            #$provider       = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientRec = new Aws\ElastiCache\ElastiCacheClient([
                'region'    => CDH_AWS_REGION,
                'version'   => CDH_AWS_CLIENT_VERSION,
                #'profile'   => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public function getClientRec(){
            return $this->clientRec;
        }

        public function setClientS3(){
            #$provider       = CredentialProvider::memoize( CredentialProvider::ini(CDH_AWS_PROFILE, CDH_CREDENTIALS_PATH) );
            $this->clientS3 = new Aws\S3\S3Client([
                'region'     => CDH_AWS_REGION,
                'version'    => CDH_AWS_CLIENT_VERSION,
                #'profile'    => CDH_AWS_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientS3(){
            return $this->clientS3;

        }

        public function setClientSss(){
            $this->setClientS3();
        }

        public function getClientSss(){
            return $this->getClientS3();
        }




        private function decode( $cadena ){
            $coder = new coder();
            return $coder->decoder($cadena);
        }

        public function setId( $id ){
            $this->id = $id;
        }


        /**
         * Esta función recibe una cadena de texto y devuelve un array.
         * 
         * Ejemplo de cadena de texto:
         *      $cadena = KO;VPC;001;58
         * 
         *  Ejemplo del array devuelto:
         * 
         *     [ok]         => [boolean] Si false hay que parar el programa o analizar código
         *     [services]   => [string:VPC|HOSTEDZONE...] 
         *     [error_code] => [string:001|002...]
         *     [line]       => [string:0 a 999999   ] nº de línea de archivo donde se genera el error 
         *     [class]      => [string:nombreDeClase] Nombre de la clase donde se ejecuta la función
         *     [msg]        => [string] ej: VPC CDH_vpc_dominio no se ha podido crear        * 
         * 
         *  Cómo tratar el array devuelto:
         *  Si los elementos OK ó ko son false se trata de un error grave, se debe de parar el programa y analizar el error
         *  El propio array nos muestra los elementos necesarios para tratar el error, nombre de la clase, linea del error y un mensaje
         * 
         * El elemento 1 de la cadena ha de ser OK ó KO 
         * El elemento 2 de la cadena ha de ser alguno de los elementos contenidos en el array CDH_AWS_SERVICES
         * El elemento 3 de la cadena ha de ser alguno de los elementos contenidos en el array CDH_ERRORS
         * El elemento 4 de la cadena es la línea del error
         * 
         * @param string $cadena    "OK|KO;service name;error_code;line_number"    
         * 
         * service(aws-vpc|aws-hz...);result(OK,KO,OKK,001~999);line;class
         *                          CODE[service,]|KO=[true|false]|OKK=true|false|file=[path]|line=[line number]|msg=[msg]
         *                          OK|KO;line;msg;file
         * 
         * @return array ['OK'=>true|false, 'ok'=true|false, 'service'=>'aws|hostedzone...', 'error_code'=>'001-999', line=[0-9999999], msg=txt, class=nombre de clase ]
         *               ['OKK'=true|false en este caso no se crea el servicio pero se usa uno ya creado previamente, OK seguirá siendo true
         * 
         */ 

        public function createResult($cadena){
             
            $cadenas = explode(";", $cadena);

            for($i=0, $resultado = array(); $i<count($cadenas); $i++ ){
                if($i == 0){ #OK|KO

                    if(preg_match("/^OK$|^KO$/i", $cadenas[$i] ) == false ){
                        var_export(  $cadenas );
                        die( "Consultar cadena de error L343: $cadena . Ha de comenzar por KO|OK"   );
                    
                    }    
                    preg_match("/^OK$/i", $cadenas[$i] ) ? $resultado['OK'] = $resultado['ok'] = true:$resultado['OK'] = $resultado['ok'] = false;
                    
                    if( $resultado['OK'] )
                        $ok='OK';
                    else  
                        $ok = "KO";
                }

                if($i == 1){#VPC|hosedzone..
                    $j = $this->in_array_ic ( $cadenas[$i], CDH_AWS_SERVICES);
                    if( !is_integer( $j ) )  
                        die( "Consultar cadena de error L355: $cadena" );

                    $cadenas[$i] =  CDH_AWS_SERVICES[$j];
                    #$resultado['msg'] = CDH_ERROR_ . $resultado['servicio'] . "_" . $cadenas[$i] ;
                    $service = $resultado['services'] = $cadenas[$i] ;
                }

                if($i == 2){#001,888,..
                    $j = $this->in_array_ic ( $cadenas[$i], CDH_ERRORS);
                    if( !is_integer( $j ) ) 
                        die( "Consultar cadena de error L363: $cadena" );
                    $error_code = $resultado['error_code'] = CDH_ERRORS[$j];
                }

                if($i == 3)#line_number
                    $resultado['line'] = $cadenas[$i];


                if( $i == 4)
                    $resultado['msg_0'] = $cadenas[$i];
                

            }
            $resultado['class'] = get_class($this);

            eval ("\$resultado['msg'] =  CDH_ERROR_" . $service . "_" . $ok . "_" . $error_code . ";"); 
            if (isset($resultado['msg_0']) ) {
                $resultado['msg'] .= PHP_EOL . $resultado['msg_0'];

            }
            #print_r( $resultado );
            return $resultado;
            
        }


        /**
         * @param mixed $needle cualquier valor que pueda contener un array
         * @param array $haystack
         * 
         * @return mixed Si el elemento    está en el array devuelve un INT con la posición del elelemto.
         *               Si el elemento NO ESTÁ en el array devuelve FALSE
         * 
         */

        public function in_array_ic($needle, $haystack) {

            for($i=0, $enArray=false; $i < count($haystack); $i++  ) {
                if( strcmp( strtolower( $needle ), strtolower( $haystack[$i] )  ) == 0 ) {
                    $enArray = true;
                    break;
                }
            }
            if( $enArray == true ) 
                return $i;

            return false;

        }

        public function getLanguage(){
            return $this->language;
        }

        public function setLanguage($lang){
            $this->language = $lang;
        }


        public function fileWriteData($path, $data){
            return file_put_contents($path, $data);
        }

        public function fileEmptyData($path){
            
            $f = @fopen( $path, "r+");
            if ($f !== false) {
                ftruncate($f, 0);
                fclose($f);
                return true;
            }
            return false;

        }

        public function isDefined( $param = null ){
            if( isset( $param ) && strlen( $param ) > 0 )
                return true;
        }

        /*
        * log mensajes para el usuario
        */
        public function logUser($mensaje){
            $logger = new CDH_Logger();
            $logger->logUser($mensaje);        
        }

        /*
        * log mensajes para el admin
        */
        public function logAdmin($mensaje){
            $logger = new CDH_Logger();
            $logger->logAdmin($mensaje);        
        }


    }

?>