<?php

use App\Http\Controllers\Admin\Admin\AdminController;
use App\Http\Controllers\Admin\Client\ClientController;
use App\Http\Controllers\Admin\Brand\BrandController;
use App\Http\Controllers\External\GhazalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('adminapi')->prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'AdminLogin']);

    Route::get('/roles', [AdminController::class, 'Roles']);
    Route::get('/roles/mysections', [AdminController::class, 'MyRoleSections']);
    Route::post('/roles/add', [AdminController::class, 'RolesAdd']);
    Route::post('/roles/edit', [AdminController::class, 'RolesEdit']);
    Route::post('/roles/sections', [AdminController::class, 'RoleSections']);
    Route::get('/roles/sections/status/{id}', [AdminController::class, 'RoleSectionStatus']);

    Route::post('/home', [AdminController::class, 'Home']);

    Route::post('/users', [AdminController::class, 'UserList']);
    Route::post('/users/add', [AdminController::class, 'AddUser']);
    Route::post('/users/language/change', [AdminController::class, 'UserLanguageChange']);
    Route::get('/users/profile/{id}', [AdminController::class, 'UserProfile']);
    Route::post('/users/edit', [AdminController::class, 'EditUser']);
    Route::post('/users/status', [AdminController::class, 'UserStatus']);

    Route::get('/countries', [AdminController::class, 'Countries']);
    Route::get('/cities/{id}', [AdminController::class, 'Cities']);
    Route::get('/areas/{id}', [AdminController::class, 'Areas']);

    Route::post('/location/countries', [AdminController::class, 'CountryList']);
    Route::get('/location/countries/status/{id}', [AdminController::class, 'CountryStatus']);
    Route::post('/location/countries/add', [AdminController::class, 'CountryAdd']);
    Route::get('/location/countries/edit/page/{id}', [AdminController::class, 'CountryEditPage']);
    Route::post('/location/countries/edit', [AdminController::class, 'CountryEdit']);

    Route::post('/location/cities', [AdminController::class, 'CityList']);
    Route::get('/location/cities/status/{id}', [AdminController::class, 'CityStatus']);
    Route::post('/location/cities/add', [AdminController::class, 'CityAdd']);
    Route::get('/location/cities/edit/page/{id}', [AdminController::class, 'CityEditPage']);
    Route::post('/location/cities/edit', [AdminController::class, 'CityEdit']);

    Route::post('/location/areas', [AdminController::class, 'AreaList']);
    Route::get('/location/areas/status/{id}', [AdminController::class, 'AreaStatus']);
    Route::post('/location/areas/add', [AdminController::class, 'AreaAdd']);
    Route::get('/location/areas/edit/page/{id}', [AdminController::class, 'AreaEditPage']);
    Route::post('/location/areas/edit', [AdminController::class, 'AreaEdit']);

    Route::post('/categories', [AdminController::class, 'CategoryList']);
    Route::get('/categories/status/{id}', [AdminController::class, 'CategoryStatus']);
    Route::post('/categories/add', [AdminController::class, 'CategoryAdd']);
    Route::get('/categories/edit/page/{id}', [AdminController::class, 'CategoryEditPage']);
    Route::post('/categories/edit', [AdminController::class, 'CategoryEdit']);
    Route::post('/categories/ajax', [AdminController::class, 'CategoryAjax']);

    Route::post('/subcategories', [AdminController::class, 'SubCategoryList']);
    Route::get('/subcategories/status/{id}', [AdminController::class, 'SubCategoryStatus']);
    Route::post('/subcategories/add', [AdminController::class, 'SubCategoryAdd']);
    Route::get('/subcategories/edit/page/{id}', [AdminController::class, 'SubCategoryEditPage']);
    Route::post('/subcategories/edit', [AdminController::class, 'SubCategoryEdit']);
    Route::post('/subcategories/ajax', [AdminController::class, 'SubCategoryAjax']);

    Route::post('/advertisements', [AdminController::class, 'AdvertisementList']);
    Route::get('/advertisements/status/{id}', [AdminController::class, 'AdvertisementStatus']);
    Route::post('/advertisements/add', [AdminController::class, 'AdvertisementAdd']);
    Route::get('/advertisements/edit/page/{id}', [AdminController::class, 'AdvertisementEditPage']);
    Route::post('/advertisements/edit', [AdminController::class, 'AdvertisementEdit']);

    Route::post('/contactus', [AdminController::class, 'ContactUs']);
    Route::get('/generalsettings', [AdminController::class, 'GeneralSettingList']);
    Route::get('/contact', [AdminController::class, 'GeneralContactList']);
    Route::post('/generalsettings/edit', [AdminController::class, 'GeneralSettingEdit']);

    Route::post('/socialmedia', [AdminController::class, 'SocialMedia']);
    Route::get('/socialmedia/status/{id}', [AdminController::class, 'SocialMediaStatus']);
    Route::post('/socialmedia/add', [AdminController::class, 'SocialMediaAdd']);
    Route::get('/socialmedia/edit/page/{id}', [AdminController::class, 'SocialMediaEditPage']);
    Route::post('/socialmedia/edit', [AdminController::class, 'SocialMediaEdit']);

    Route::post('/events', [AdminController::class, 'EventList']);
    Route::post('/events/status', [AdminController::class, 'EventStatus']);
    Route::post('/events/add', [AdminController::class, 'EventAdd']);
    Route::post('/events/details', [AdminController::class, 'EventDetails']);
    Route::post('/events/edit', [AdminController::class, 'EventEdit']);
    Route::get('/events/gallery/remove/{id}', [AdminController::class, 'EventGalleryRemove']);
    Route::get('/events/attendee/remove/{id}', [AdminController::class, 'EventAttendeeRemove']);

    Route::post('/tools', [AdminController::class, 'ToolList']);
    Route::post('/tools/status', [AdminController::class, 'ToolStatus']);
    Route::post('/tools/add', [AdminController::class, 'ToolAdd']);
    Route::post('/tools/details', [AdminController::class, 'ToolDetails']);
    Route::post('/tools/edit', [AdminController::class, 'ToolEdit']);
    Route::get('/tools/gallery/remove/{id}', [AdminController::class, 'ToolGalleryRemove']);

    Route::post('/clients', [AdminController::class, 'ClientList']);
    Route::post('/clients/status', [AdminController::class, 'ClientStatus']);
    Route::post('/clients/balance/update', [AdminController::class, 'ClientBalanceUpdate']);
    Route::post('/clients/ledger', [AdminController::class, 'ClientLedger']);

    Route::post('/company/ledger', [AdminController::class, 'CompanyLedger']);
    Route::post('/company/ledger/add', [AdminController::class, 'CompanyLedgerAdd']);
    Route::get('/company/ledger/edit/page/{id}', [AdminController::class, 'CompanyLedgerEditPage']);
    Route::post('/company/ledger/edit', [AdminController::class, 'CompanyLedgerEdit']);

    Route::post('/action/backlog', [AdminController::class, 'ActionBackLog']);

    Route::post('/brands', [BrandController::class, 'BrandList']);
    Route::post('/brands/status', [BrandController::class, 'BrandStatus']);
    Route::post('/brands/add', [BrandController::class, 'BrandAdd']);
    Route::get('/brands/edit/page/{id}', [BrandController::class, 'BrandEditPage']);
    Route::post('/brands/edit', [BrandController::class, 'BrandEdit']);
    Route::post('/brands/ajax', [BrandController::class, 'BrandAjax']);
    Route::get('/brands/documents/{id}', [BrandController::class, 'BrandDocuments']);
    Route::get('/brands/document/remove/{id}', [BrandController::class, 'BrandDocumentRemove']);

    Route::post('/brands/contacts', [BrandController::class, 'BrandContactList']);
    Route::get('/brands/contacts/delete/{id}', [BrandController::class, 'BrandContactDelete']);
    Route::post('/brands/contacts/add', [BrandController::class, 'BrandContactAdd']);
    Route::get('/brands/contacts/edit/page/{id}', [BrandController::class, 'BrandContactEditPage']);
    Route::post('/brands/contacts/edit', [BrandController::class, 'BrandContactEdit']);

    Route::post('/brands/gallery', [BrandController::class, 'BrandGallery']);
    Route::get('/brands/gallery/remove/{id}', [BrandController::class, 'BrandGalleryRemove']);
    Route::post('/brands/gallery/add', [BrandController::class, 'BrandGalleryAdd']);

    Route::post('/brands/socialmedia', [BrandController::class, 'BrandSocialMedia']);
    Route::post('/brands/socialmedia/status', [BrandController::class, 'BrandSocialMediaStatus']);


    Route::post('/brands/contracts', [BrandController::class, 'BrandContractList']);
    Route::post('/brands/contracts/add', [BrandController::class, 'BrandContractAdd']);
    Route::get('/brands/contracts/edit/page/{id}', [BrandController::class, 'BrandContractEditPage']);
    Route::post('/brands/contracts/edit', [BrandController::class, 'BrandContractEdit']);
    Route::get('/brands/contracts/documents/{id}', [BrandController::class, 'BrandContractDocuments']);
    Route::get('/brands/contracts/document/remove/{id}', [BrandController::class, 'BrandContractDocumentRemove']);

    Route::post('/branches', [BrandController::class, 'BranchList']);
    Route::post('/branches/status', [BrandController::class, 'BranchStatus']);
    Route::post('/branches/add', [BrandController::class, 'BranchAdd']);
    Route::get('/branches/edit/page/{id}', [BrandController::class, 'BranchEditPage']);
    Route::post('/branches/edit', [BrandController::class, 'BranchEdit']);
    Route::post('/branches/ajax', [BrandController::class, 'BranchAjax']);

    Route::post('/brands/products', [BrandController::class, 'BrandProductList']);
    Route::post('/brands/products/status', [BrandController::class, 'BrandProductStatus']);
    Route::post('/brands/products/add', [BrandController::class, 'BrandProductAdd']);
    Route::get('/brands/products/edit/page/{id}', [BrandController::class, 'BrandProductEditPage']);
    Route::post('/brands/products/edit', [BrandController::class, 'BrandProductEdit']);
    Route::get('/brands/products/gallery/remove/{id}', [BrandController::class, 'BrandProductGalleryRemove']);
    Route::post('/brands/products/ajax', [BrandController::class, 'BrandProductAjax']);
    Route::post('/brands/products/branches', [BrandController::class, 'BrandProductBranches']);
    Route::post('/brands/products/branches/status', [BrandController::class, 'BrandProductBranchStatus']);

    Route::post('/brands/ratings', [BrandController::class, 'BrandRatingList']);
    Route::post('/brands/ratings/status', [BrandController::class, 'BrandRatingStatus']);

    Route::post('/brands/contactus', [BrandController::class, 'BrandContactUsList']);
    Route::get('/brands/contactus/status/{id}', [BrandController::class, 'BrandContactUsStatus']);

    Route::post('/clients/register', [ClientController::class, 'ClientRegister']);
    Route::post('/clients/network/add', [ClientController::class, 'ClientNetworkAdd']);
    Route::post('/clients', [ClientController::class, 'ClientList']);
    Route::get('/clients/details/{id}', [ClientController::class, 'ClientDetails']);
    Route::post('/clients/status', [ClientController::class, 'ClientStatus']);
    Route::post('/clients/balance', [ClientController::class, 'ClientBalanceSet']);
    Route::post('/clients/rewardpoints', [ClientController::class, 'ClientRewardPointSet']);
    Route::post('/clients/balance/transfer', [ClientController::class, 'BalanceTransfer']);
    Route::post('/clients/events', [ClientController::class, 'ClientEvents']);
    Route::post('/clients/tools', [ClientController::class, 'ClientTools']);
    Route::post('/clients/brandproducts', [ClientController::class, 'ClientBrandProducts']);
    Route::post('/clients/position/update', [ClientController::class, 'ClientPositionUpdate']);
    Route::post('/clients/ledger', [ClientController::class, 'ClientLedger']);
    Route::post('/clients/rename', [ClientController::class, 'ClientRename']);
    Route::post("/clients", [ClientController::class, "ClientCheck"]);

    Route::post('/positions', [ClientController::class, 'PositionList']);
    Route::post('/positions/status', [ClientController::class, 'PositionStatus']);
    Route::post('/positions/add', [ClientController::class, 'PositionAdd']);
    Route::get('/positions/edit/page/{id}', [ClientController::class, 'PositionEditPage']);
    Route::post('/positions/edit', [ClientController::class, 'PositionEdit']);
    Route::post('/positions/ajax', [ClientController::class, 'PositionAjax']);
    Route::post('/positions/brands', [ClientController::class, 'PositionBrandList']);
    Route::post('/positions/brands/status', [ClientController::class, 'PositionBrandStatus']);
    Route::post('/positions/clients', [ClientController::class, 'PositionClients']);

    Route::post('/plans', [ClientController::class, 'PlanList']);
    Route::post('/plans/status', [ClientController::class, 'PlanStatus']);
    Route::post('/plans/add', [ClientController::class, 'PlanAdd']);
    Route::get('/plans/edit/page/{id}', [ClientController::class, 'PlanEditPage']);
    Route::post('/plans/edit', [ClientController::class, 'PlanEdit']);
    Route::post('/plans/ajax', [ClientController::class, 'PlanAjax']);

    Route::post('/plans/products', [ClientController::class, 'PlanProductList']);
    Route::post('/plans/products/status', [ClientController::class, 'PlanProductStatus']);
    Route::post('/plans/products/add', [ClientController::class, 'PlanProductAdd']);
    Route::get('/plans/products/edit/page/{id}', [ClientController::class, 'PlanProductEditPage']);
    Route::post('/plans/products/edit', [ClientController::class, 'PlanProductEdit']);
    Route::get('/plans/products/gallery/remove/{id}', [ClientController::class, 'PlanProductGalleryRemove']);
    Route::post('/plans/products/social', [ClientController::class, 'PlanProductSocialList']);
    Route::post('/plans/products/social/add', [ClientController::class, 'PlanProductSocialAdd']);
    Route::post('/plans/products/social/status', [ClientController::class, 'PlanProductSocialStatus']);
    Route::post('/plans/products/ajax', [ClientController::class, 'PlanProductAjax']);

    Route::post('/plans/products/upgrades', [ClientController::class, 'PlanProductUpgrades']);
    Route::get('/plans/products/upgrades/status/{id}', [ClientController::class, 'PlanProductUpgradeStatus']);
    Route::post('/plans/products/upgrades/add', [ClientController::class, 'PlanProductUpgradeAdd']);
    Route::get('/plans/products/upgrades/edit/page/{id}', [ClientController::class, 'PlanProductUpgradeEditPage']);
    Route::post('/plans/products/upgrades/edit', [ClientController::class, 'PlanProductUpgradeEdit']);


    Route::post('/bonanza', [ClientController::class, 'BonanzaList']);
    Route::post('/bonanza/status', [ClientController::class, 'BonanzaStatus']);
    Route::post('/bonanza/add', [ClientController::class, 'BonanzaAdd']);
    Route::get('/bonanza/edit/page/{id}', [ClientController::class, 'BonanzaEditPage']);
    Route::post('/bonanza/edit', [ClientController::class, 'BonanzaEdit']);
    Route::post('/bonanza/brands', [ClientController::class, 'BonanzaBrandList']);
    Route::post('/bonanza/brands/status', [ClientController::class, 'BonanzaBrandStatus']);
    Route::post('/bonanza/clients', [ClientController::class, 'BonanzaClients']);

    Route::post('/clients/chat', [ClientController::class, 'ClientChatList']);
    Route::post('/clients/chat/details', [ClientController::class, 'ClientChatDetails']);

    Route::post('/nationalities', [AdminController::class, 'NationalityList']);
    Route::post('/nationalities/add', [AdminController::class, 'NationalityAdd']);
    Route::get('/nationalities/edit/page/{id}', [AdminController::class, 'NationalityEditPage']);
    Route::post('/nationalities/edit', [AdminController::class, 'NationalityEdit']);


    Route::post('/test', [GhazalController::class, 'RequestPayment']);
    Route::post('/test2', [GhazalController::class, 'PaymentCallBack']);
    Route::get('/test3/{id}', [GhazalController::class, 'PaymentInvoice']);

    Route::post('/clients/test', [ClientController::class, 'test']);
});
