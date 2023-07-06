# Lazada-sdk
Lazada marketplace api integration sdk.
+ Seller panel
    https://sellercenter.lazada.com.my/seller/login
+ Api Explorer: https://sellercenter.lazada.com.my/api/index
+ Api Documentation: https://lazada-sellercenter.readme.io/docs
+ Api Support Url: https://crossborder.lazada.com/scapi/
+ Seller Sign Up Url: https://lazadacb.formstack.com/forms/become_a_seller_psc
+ Seller Center Url : https://lazadahkpsc.zendesk.com/hc/en-us/categories/200558555-Support-Center
+ For api credentials, need to contact Technical Manager from support. In our case it was: prashan.selva@lazada.com.my 

# User Guide
### Installation
+ ##### Manual Way 
    + Create "cedcoss" directory in vendor directory
    + run below command in cedcoss directory
                        
            git clone https://github.com/cedcoss/lazada-sdk.git
    + now open composer.json present in root directory and add below lines in it
    
            "autoload": {
                 "psr-4": {
                "Lazada\\Sdk\\": "vendor/cedcoss/lazada-sdk/src/"
                }
            }
    + after that run below command
    
            composer dump
    
+ ##### Install through composer 
    + Run Below commands in your root directory (Make sure ssh key setup is done fore this repo)
    
            composer config repositories.cedcoss/lazada-sdk git git@github.com:cedcoss/lazada-sdk.git
            
            composer require cedcoss/lazada-sdk:dev-master
            
## Endpoints
+ GetCategoryTree
    `https://api.sellercenter.lazada.sg?Action=GetCategoryTree`
+ GetProducts
    `https://api.sellercenter.lazada.sg?Action=GetProducts`
+ SearchSPUs
    `https://api.sellercenter.lazada.sg?Action=SearchSPUs`
+ UploadImage
    `https://api.sellercenter.lazada.sg?Action=UploadImage`
+ UploadImages
    `https://api.sellercenter.lazada.sg/?Action=UploadImages`
+ MigrateImage    
    `https://api.sellercenter.lazada.sg?Action=MigrateImage` 
+ MigrateImages
    `https://api.sellercenter.lazada.sg?Action=MigrateImages`
+ GetResponse
    `https://api.sellercenter.lazada.sg/?Action=GetResponse`
+ CreateProduct
    `https://api.sellercenter.lazada.sg?Action=CreateProduct`
+ UpdateProduct
    `https://api.sellercenter.lazada.sg?Action=UpdateProduct`
+ SetImages
    `https://api.sellercenter.lazada.sg?Action=SetImages`
+ UpdatePriceQuantity
    `https://api.sellercenter.lazada.sg?Action=UpdatePriceQuantity`
+ RemoveProduct
    `https://api.sellercenter.lazada.sg?Action=RemoveProduct`
+ GetBrands
    `https://api.sellercenter.lazada.sg?Action=GetBrands`
+ GetCategoryAttributes
    `https://api.sellercenter.lazada.sg?Action=GetCategoryAttributes`
+ GetOrders
    `https://api.sellercenter.lazada.sg?Action=GetOrders`
+ GetOrder
    `https://api.sellercenter.lazada.sg?Action=GetOrder`
+ GetOrderItems
    `https://api.sellercenter.lazada.sg?Action=GetOrderItems`
+ GetMultipleOrderItems
    `https://api.sellercenter.lazada.sg?Action=GetMultipleOrderItems`
+ SetStatusToCanceled
    `https://api.sellercenter.lazada.sg?Action=SetStatusToCanceled`
+ SetStatusToPackedByMarketplace
    `https://api.sellercenter.lazada.sg?Action=SetStatusToPackedByMarketplace`
+ SetStatusToReadyToShip
    `https://api.sellercenter.lazada.sg?Action=SetStatusToReadyToShip`
+ GetDocument    
    `https://api.sellercenter.lazada.sg?Action=GetDocument`
+ GetFailureReasons
    `https://api.sellercenter.lazada.sg?Action=GetFailureReasons`
+ SetInvoiceNumber
    `https://api.sellercenter.lazada.sg?Action=SetInvoiceNumber`
+ GetShipmentProviders
    `https://api.sellercenter.lazada.sg?Action=GetShipmentProviders`
+ Definition
    `https://api.sellercenter.lazada.sg?Action=GetMetrics`
+ GetPayoutStatus
    `https://api.sellercenter.lazada.sg?Action=GetPayoutStatus`
+ GetStatistics
    `https://api.sellercenter.lazada.sg?Action=GetStatistics`
+ SellerUpdate
    `https://api.sellercenter.lazada.sg?Action=SellerUpdate`
+ UserUpdate
    `https://api.sellercenter.lazada.sg?Action=UserUpdate`
+ GetTransactionDetails
    `https://api.sellercenter.lazada.sg?Action=GetTransactionDetails`
+ GetQcStatus
    `https://api.sellercenter.lazada.sg?Action=GetQcStatus`