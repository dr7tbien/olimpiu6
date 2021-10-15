<?php

    /**
    * Just another function to inherit.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0
    * @abstract
    *
    **/



    require_once 'vendor/autoload.php';
    require_once 'defines/CDH_PS_awsDefineAll.php';
    //require_once 'defines/CDH_PS_awsDefineAll.php';
    
    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;


    abstract class CDH_PS_abstractAwsService{

        private $cloudObject;

        private $credentials;
        private $id;

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
        private $clientSss;


        #ßabstract public function __construct();
        abstract public function create             ();
        
        abstract public function getFieldValueById  ($x , $y);
        abstract public function getFieldValueByName($x, $y);
        abstract public function getTagValue        ($tagKey , $vpcId, $vpcName);
        abstract public function getServiceById     ($id);
        abstract public function getServiceByName   ($name );
        abstract public function delete             ($name);
        abstract public function exists             ($vpcId, $vpcTagName);
        abstract public function isAvailable        ($vpcId , $vpcTagName );
        abstract public function waitToBeCreated    ($vpcName, $sleeper, $times);
        
        abstract public function getId              ($vpcTagName);
        

        

        function setCloudObject( $cloudObject ){
            $this->cloudObject = $cloudObject;
        }

        function getCloudObject(){
            return $this->cloudObject;
        }

        function getDataFromArray( $aguja, $pajar ){

            foreach( $pajar as $key => $value ) {
                if( $key == $aguja  )
                    return $value;

                if(  is_array( $value)  || is_array( $key ) || is_array( $pajar[ $key ]  )   )
                    getDataFromArray( $aguja, $pajar[$key]);

                #if( is_array( $value  )  ) continue;

                
            }
            return false;
        }


        public function setClientAcm(){
            
            $this->clientAcm = new Aws\Acm\AcmClient ([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientAcm(){
            return $this->clientAcm;
        }

        public  function setClientAlb(){
            $credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
            $this->clientAlb = new  Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client ([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public  function getClientAlb() {
            return $this->clientAlb;
        }

        public function setClientAsc(){
            $credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
            $this->clientAsc = new Aws\AutoScaling\AutoScalingClient([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }
    
        public function getClientAsc(){
            return $this->clientAsc;
        }

        public function setClientCwt(){
            $credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
            $this->clientCwt = new Aws\CloudWatch\CloudWatchClient ([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientCwt(){
            return $this->clientCwt;
        }

        public function setClientDsy(){
            $this->clientDsy = new Aws\DataSync\DataSyncClient([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientDsy(){
            return $this->clientDsy;
        }

        public function setClientEc2(){
            $this->clientEc2 = new Aws\Ec2\Ec2Client([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientEc2(){
            return $this->clientEc2;
        }

        public function setClientEcc(){
            $this->clientEcc = new Aws\ElastiCache\ElastiCacheClient([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public function getClientEcc(){
            return $this->clientEcc;
        }

        private function setClientEfs() {

            $this->clientEfs = new Aws\Efs\EfsClient ([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        private function getClientEfs(){
            return $this->clientEfs;
        }

        public function setClientIam(){
            $this->clientIam = new Aws\Iam\IamClient([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientIam(){
            return $this->clientIam;
        }

        public  function setClientLb2(){
            $this->clientLb2 = new  Aws\ElasticLoadBalancingV2\ElasticLoadBalancingV2Client ([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
            
    
        }
    
        public  function getClientLb2() {
            return $this->clientLb2;
        }

        public function setClientR53(){
            $this->clientR53 = new Aws\Route53\Route53Client([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientR53(){
            return $this->clientR53;
        }

        public function setClientRds(){
            $this->clientRds = new Aws\Rds\RdsClient([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientRds(){
            return $this->clientRds;
        }

        public function setClientRec(){
            $this->clientRec = new Aws\ElastiCache\ElastiCacheClient([
                'region'    => VPC_REGION,
                'version'   => VPC_VERSION,
                'profile'   => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);

        }

        public function getClientRec(){
            return $this->clientRec;
        }

        public function setClientSss(){
            
            $this->clientSss = new Aws\S3\S3Client([
                'region'     => VPC_REGION,
                'version'    => VPC_VERSION,
                'profile'    => VPC_PROFILE,
                'credentials' => $this->getCredentials()
            ]);
        }

        public function getClientSss(){
            return $this->clientSss;

        }

        public function setCredentials(){
            $this->credentials = new Credentials($this->decode( AWS_ACCESS_KEY_ID ), $this->decode( AWS_SECRET_ACCESS_KEY ));
        }

        public function getCredentials(){
            return $this->credentials;
        }

        private function decode( $cadena ){
            return $cadena;
        }

        public function setId( $id ){
            $this->id = $id;
        }

    }

?>