<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    require_once 'defines/CDH_awsDefinesAll.php'                                            ;
    require_once 'classes/CDH_abstractAwsCloudator.php';
    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    
    
    #require_once 'classes/CDH_awsRequestCertificate.php';   


    #require_once 'classes/CDH_awsVpc.php';        
    #require_once 'classes/CDH_awsSecurityGroup.php';
    
    #require_once 'classes/CDH_awsElasticIp.php';
    
    #require_once 'classes/CDH_awsInternetGateway.php';      
    #require_once 'classes/CDH_awsSubnet.php';      
    #require_once 'classes/CDH_awsNatGateway.php';      
    #require_once 'classes/CDH_awsRouteTable.php';   
              

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep0NetSystem extends CDH_abstractAwsCloudator {

        

        public $subnets             = [];
        


        public function __construct(){
            $this->createNames();
        }
        
        
        public function createForCloudator() {
          
            #region vpc
            /*if( $this->createVpc() == false ){
                $this->logUser('{"one":"\nL052 - Vpc NO creado\n"},');
                return false;
            }
            $this->logUser('{"one":"\nL055 - Vpc creado\n"},');
            return true;*/
            #endregion vpc

            #region internetGateway
            if( $this->createInternetGateway() == false ){
                $this->logUser('{"one":"\nL060 - IG NO creado\n"},');
                return false;
            }
            $this->logUser('{"one":"\nL063 - InternetGateway creado\n"},');            
            #endregion internetGateway

            #region subnets
            if( $this->createSubnets() == false ){
                $this->logUser('{"one":"\nL068 - Subnet NO creado\n"},');
                return false;
            }
            $this->logUser('{"one":"\nL071 - Subnets creado\n"},');
            #endregion subnets

            #region natgateway
            if(  $this->createNatGateway() == false ){
                $this->logUser('{"one":"\nL076 - NAT NO creado\n"},');
                return false;
            }
            $this->logUser('{"one":"\nL065 - Nat Gateway creado\n"},');
            #endregion natgateway
            
            #region routetables
            if( $this->createRouteTables() == false ){
                $this->logUser('{"one":"\nL084 - RTs NO creado\n"},');
                return false;
            }
            $this->logUser('{"one":"\nL069 - Route Tables creado\n"},');
            #endregion routetables
            

            #region RequestCertificates
            #if( $this->createRequestCertificates() == false ){
            #    $this->logUser('{"one":"\nL093 - RCs NO creados\n";
            #    return false;
            #}
            #$this->logUser('{"one":"\nL069 - RCs creados\n";
            #endregion RequestCertificates

            $this->logUser('{"one":"\nL071 - TODO TODITO creado\n"},');
            
            return true;

        }





        public function createVpc(){

            $vpc        = new CDH_awsVpc();
            $CDH_NET_SYSTEM_VPC = CDH_NET_SYSTEM_VPC;
            #print_r($CDH_NET_SYSTEM_VPC); die();
            $service    = $vpc->create( $CDH_NET_SYSTEM_VPC );
            if( $service != true)
                return false;
                
            return true;
        }

        public function createInternetGateway(){
            $internetGateway    = new CDH_awsInternetGateway();
            $vpc                = new CDH_awsVpc();
            $vpcId              = $vpc->getIdByName( CDH_VPC_NAME );
            $service            = $internetGateway->create        ( CDH_NET_SYSTEM_INTERNET_GATEWAY ); 
            $resultAttach       = $internetGateway->attachToVpc   ( $service[ 'InternetGatewayId' ], $vpcId );
            if( $service != true || $resultAttach   != true ){
                $this->logUser('{"one":"\nL130 - IG Not attached \n"},');
                return false;
            }     

            $this->logUser('{"one":"\nL135 - IG created and attached \n"},');
            return true;
        }



        public function createSubnets(){

            $sn     = new CDH_awsSubnet();
            $vpc    = new CDH_awsVpc();

            $vpcId  = $vpc->getIdByName( CDH_VPC_NAME  );

            $subnets = array(); 
            foreach( CDH_NET_SYSTEM_SUBNETS as $subnet ) {
                $subnet['VpcId'] = $vpcId;
                $service = $sn->create( $subnet );
                if( $service == false ) {
                    $this->logUser('{"one":"\nNo se ha podido crear subnet\n"},');
                    var_export( $service );
                    return false;
                }
                $subnets[ $service['Tags'][0]['Value'] ] = $service;
                sleep(5);
            }

            $this->setSubnets( $subnets );
            return true;
        }


        public function createNatGateway(){
            $eIp = new CDH_awsElasticIp();
            $nat = new CDH_awsNatGateway();
            $subnet = new CDH_awsSubnet();
            $nats = array();

            $CDH_NET_SYSTEM_NAT_GATEWAY          = CDH_NET_SYSTEM_NAT_GATEWAY;
            $CDH_NET_SYSTEM_NAT_ALLOCATE_ADDRESS = CDH_NET_SYSTEM_NAT_ALLOCATE_ADDRESS;            
            #crear elasticIp
            if( !$eIp->create( $CDH_NET_SYSTEM_NAT_ALLOCATE_ADDRESS ) ) {
                $this->logAdmin( "\n\L173 Error creando ip elástica" ); 
                return false;               
            }
            
            $nameEip = $CDH_NET_SYSTEM_NAT_ALLOCATE_ADDRESS['TagSpecifications'][0]['Tags'][0]['Value'];
            
            $CDH_NET_SYSTEM_NAT_GATEWAY['AllocationId'] = $eIp->getIdByName( $nameEip );
            
            #print_r( $CDH_NET_SYSTEM_NAT_GATEWAY ); die();
            #$res['AllocationId'];
            #buscar subnetId
            
            #$subnets = CDH_NET_SYSTEM_SUBNETS;
            $subnetId = $subnet->getIdByName( 'CDH_PUBLIC_0_' . CDH_DOMINIO_SIN_PUNTO );
            #$subnetId = $subnets[ 'CDH_PUBLIC_0_' . CDH_DOMINIO_SIN_PUNTO ]['SubnetId'];
            $CDH_NET_SYSTEM_NAT_GATEWAY['SubnetId'] = $subnetId;

            $service = $nat->create( $CDH_NET_SYSTEM_NAT_GATEWAY );
            if( $service == false ) {
                $this->logAdmin( "\n L089 - No se puede crear NAT \n");
            }
            
            return true;
            

        }



        public function createRouteTables() {
            $rt             = new CDH_awsRouteTable();
            $subnet         = new CDH_awsSubnet();             
            $route          = new CDH_awsRoute();
            $natGateway     = new CDH_awsNatGateway();
            $internetGateway= new CDH_awsInternetGateway();
	        $vpc            = new CDH_awsVpc();


            #$vpcName            = $this->getVpcName(); 
            $vpcId              = $vpc->getIdByName             ( CDH_VPC_NAME              );
            $natGatewayId       = $natGateway->getIdByName      ( CDH_NET_SYSTEM_NAT_GATEWAY['TagSpecifications'][0]['Tags'][0]['Value']        );
            $internetGatewayId  = $internetGateway->getIdByName ( CDH_NET_SYSTEM_INTERNET_GATEWAY['TagSpecifications'][0]['Tags'][0]['Value']   );

            $routeTables    = CDH_NET_SYSTEM_ROUTE_TABLES;
            $rts            = [];
            $routes         = CDH_NET_SYSTEM_ROUTES;
            
            for( $i = 0; $i < count($routeTables); $i++ ){
            #foreach( $routeTables as $routeTable ){
                $routeTables[$i]['VpcId'] = $vpcId;
                $service = $rt->create( $routeTables[$i] );
                if($service == false){
                    $this->logUser('{"one":"\nL234 no se puede crear RT\n"},');
                    return false;
                }
                $name = $service['Tags'][0]['Value']  ;
                $id   = $service['RouteTableId'];
                
                $rts [ $name ] = $id;
                $routes[$i]['RouteTableId'] = $id;

                sleep( 5 );
            }
            
            $route                  = new CDH_awsRoute();
            $routes[0]['GatewayId'] = $internetGatewayId;
            $routes[1]['GatewayId'] = $natGatewayId;
            $routes[2]['GatewayId'] = $internetGatewayId;

            foreach( $routes as $rt ) {

                if ($route->create( $rt ) == false ) {
                   $this->logAdmin('{"one":"\nL274 Error creando ruta \n"},');
                   return false;
                }


            }
            $rt             = new CDH_awsRouteTable();
            $associations = CDH_NET_SYSTEM_ROUTE_TABLES_ASSOCIATIONS;

            foreach( $associations as $subnetName => $routeTableName ){
                $associanteData= [
                    'RouteTableId'  => $rts[ $routeTableName ], // REQUIRED
                    'SubnetId'      => $subnet->getIdByName( $subnetName  ),
                ];
                $rt->associate( $associanteData );    
            }

            return true;
        
        }


        public function createRequestCertificates(){
            $rc     = new CDH_awsRequestCertificate();
            
            #$client = $rc->getClientAcm();
            $result = $rc->create( CDH_NET_SYSTEM_REQUEST_CERTIFICATE ); 
            if ( $result == false )
                return false;

            $region = 'us-east-1';
            $rc->setClientAcm( $region );

            #$client = $rc->getClientAcm();
            $result = $rc->create( CDH_NET_SYSTEM_REQUEST_CERTIFICATE ); 
            if ( $result == false )
                return false;

            return true;
        }



        public function setSubnets( $subnets ){
            $this->subnets = $subnets;
        }

        public function getSubnets(){
            return $this->subnets;
        }




    }

?>
