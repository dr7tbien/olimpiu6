<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    require_once 'defines/CDH_awsDefinesAll.php'					;
    require_once 'classes/CDH_abstractAwsService.php'					;
    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php'			;
    require_once 'defines/services_aws/CDH_awsDefineStep00RecordSetSystem.php'		;
    require_once 'defines/services_aws/CDH_awsDefineStep1FileSystem.php'		;
    require_once 'defines/services_aws/CDH_awsDefineStep2DbSystem.php'			;
    require_once 'defines/services_aws/CDH_awsDefineStep3ImageSystem.php'		;
    require_once 'defines/services_aws/CDH_awsDefineStep4LoadBalancingSystem.php'	;
    require_once 'defines/services_aws/CDH_awsDefineStep5AutoScalingGroupFrontEndSystem.php';
  
    require_once 'classes/CDH_awsVpc.php';        
    require_once 'classes/CDH_awsElasticIp.php';    
    require_once 'classes/CDH_awsInternetGateway.php';      
    require_once 'classes/CDH_awsSubnet.php';      
    require_once 'classes/CDH_awsNatGateway.php';      
    require_once 'classes/CDH_awsRouteTable.php';   
             
    require_once 'classes/CDH_awsFileSystem.php'; 
    require_once 'classes/CDH_awsSecurityGroup.php'; 
    require_once 'classes/CDH_awsMountTarget.php';   
    require_once 'classes/CDH_awsAccessPoint.php';   
    
    require_once 'classes/CDH_Logger.php';
    


    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    abstract class CDH_abstractAwsCloudator{


        private $securityGroupNameEfs;
        private $fileSystemName;

        private $vpcName;

        private $internetGatewayName;
        private $natGatewayName;
                
        private $subnetPublic0Name;
        private $subnetPublic1Name;
        private $subnetPublic2Name;

        private $subnetPrivate0Name;
        private $subnetPrivate1Name;
        private $subnetPrivate2Name;

        private $subnetDatabase0Name;
        private $subnetDatabase1Name;
        private $subnetDatabase2Name;
        
        private $instanceProfileImageName;
        private $securityGroupNameImage;

        private $securityGroupNameDb;
        private $securityGroupNameFileSystem;

        private $instanceName;
        

        abstract public function createForCloudator              ();

        public function createNames(){
            $this->setVpcName               ( CDH_NET_SYSTEM_VPC                ['TagSpecifications'][0]['Tags'][0]['Value'] );
            
            $this->setInternetGatewayName   ( CDH_NET_SYSTEM_INTERNET_GATEWAY   ['TagSpecifications'][0]['Tags'][0]['Value'] );
            $this->setNatGatewayName        ( CDH_NET_SYSTEM_NAT_GATEWAY        ['TagSpecifications'][0]['Tags'][0]['Value'] );
            

            $this->setSubnetPublic0Name     ( CDH_NET_SYSTEM_SUBNETS[0]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $this->setSubnetPublic1Name     ( CDH_NET_SYSTEM_SUBNETS[1]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $this->setSubnetPublic2Name     ( CDH_NET_SYSTEM_SUBNETS[2]         ['TagSpecifications'][0]['Tags'][0]['Value']);

            $this->setSubnetPrivate0Name    ( CDH_NET_SYSTEM_SUBNETS[3]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $this->setSubnetPrivate1Name    ( CDH_NET_SYSTEM_SUBNETS[4]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $this->setSubnetPrivate2Name    ( CDH_NET_SYSTEM_SUBNETS[5]         ['TagSpecifications'][0]['Tags'][0]['Value']);

            $this->setSubnetDatabase0Name   ( CDH_NET_SYSTEM_SUBNETS[6]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $this->setSubnetDatabase1Name   ( CDH_NET_SYSTEM_SUBNETS[7]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $this->setSubnetDatabase2Name   ( CDH_NET_SYSTEM_SUBNETS[8]         ['TagSpecifications'][0]['Tags'][0]['Value']);
            
            $this->setFileSystemName                ( CDH_FILESYSTEM                ['Tags'][0]['Value'] );
            $this->setSecurityGroupNameFileSystem   ( CDH_FILESYSTEM_SECURITY_GROUP ['TagSpecifications'][0]['Tags'][0]['Value'] );
            $this->setSecurityGroupNameDb           ( CDH_DB_INSTANCE_createSecurityGroup   ['TagSpecifications'][0]['Tags'][0]['Value'] );

            #$this->setInstanceProfileImageName  ( CDH_IMAGE_INSTANCE_PROFILE['InstanceProfileName']);
            #$this->setSecurityGroupNameImage    ( CDH_IMAGE_SECURITY_GROUP  ['GroupName']          );

            #$this->setInstanceName              ( CDH_IMAGE_INSTANCE        ['TagSpecifications'][0]['Tags'][0]['Value']);
        }
        
        private function setInternetGatewayName( $data ){
            $this->internetGatewayName = $data;
        }

        public function getInternetGatewayName(){
            return $this->internetGatewayName;
        }


        private function setNatGatewayName( $data ){
            $this->natGatewayName = $data;
        }

        public function getNatGatewayName(){
            return $this->natGatewayName;
        }

        public function setSecurityGroupNameDb( $data ){
            $this->securityGroupNameDb = $data;
        }

        public function getSecurityGroupNameDb(){
            return $this->securityGroupNameDb;
        }


        public function setInstanceName( $data ){
            $this->instanceName = $data;
        }

        public function getInstanceName(){
            return $this->instanceName;
        }

        
        
        public function setInstanceProfileImageName( $data ){
            $this->instanceProfileImageName = $data;
        }

        public function getInstanceProfileImageName(){
            return $this->instanceProfileImageName;
        }

        public function setSecurityGroupNameImage( $data ){
            $this->securityGroupNameImage = $data;
        }

        public function getSecurityGroupNameImage(){
            return $this->securityGroupNameImage;
        }


        public function setFileSystemName( $data ){
            $this->fileSystemName = $data;
        }
    
        public function getFileSystemName(){
            return $this->fileSystemName;
        }

        public function setVpcName( $data ){
            $this->vpcName = $data;
        }
    
        public function getVpcName(){
            return $this->vpcName;
        }


        public function setSecurityGroupNameFileSystem( $data ){
            $this->securityGroupNameFileSystem = $data;
        }
    
        public function getSecurityGroupNameFileSystem(){
            return $this->securityGroupNameFileSystem;
        }
    
        
        public function setSubnetPublic0Name    ( $data ){
            $this->subnetPublic0Name = $data;
        }

        public function setSubnetPublic1Name    ( $data ){
            $this->subnetPublic1Name = $data;
        }

        public function setSubnetPublic2Name    ( $data ){
            $this->subnetPublic2Name = $data;
        }

        public function setSubnetPrivate0Name   ( $data ){
            $this->subnetPrivate0Name = $data;
        }

        public function setSubnetPrivate1Name   ( $data ){
            $this->subnetPrivate1Name = $data;
        }

        public function setSubnetPrivate2Name   ( $data ){
            $this->subnetPrivate2Name = $data;
        }

        public function setSubnetDatabase0Name  ( $data ){
            $this->subnetDatabase0Name = $data;
        }

        public function setSubnetDatabase1Name  ( $data ){
            $this->subnetDatabase1Name = $data;
        }
        
        public function setSubnetDatabase2Name  ( $data ){
            $this->subnetDatabase2Name = $data;
        }



        public function getSubnetPublic0Name    (){
            return $this->subnetPublic0Name;
        }

        public function getSubnetPublic1Name    (){
            return $this->subnetPublic1Name;
        }
        public function getSubnetPublic2Name    (){
            return $this->subnetPublic2Name;
        }

        public function getSubnetPrivate0Name   (){
            return $this->subnetPrivate0Name;
        }

        public function getSubnetPrivate1Name   (){
            return $this->subnetPrivate1Name;
        }

        public function getSubnetPrivate2Name   (){
            return $this->subnetPrivate2Name;
        }

        public function getSubnetDatabase0Name  (){
            return $this->subnetDatabase0Name;
        }

        public function getSubnetDatabase1Name  (){
            return $this->subnetDatabase1Name;
        }
        
        public function getSubnetDatabase2Name  (){
            return $this->subnetDatabase2Name;
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
