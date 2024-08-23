<?php

use App\Http\Controllers\App\Client\ClientController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::middleware('clientapi')->prefix('client')->group(function () {
    Route::get('/terms', [ClientController::class, 'Terms']);
    Route::get('/privacy', [ClientController::class, 'PrivacyPolicy']);
    Route::get('/about', [ClientController::class, 'AboutUs']);
    Route::post('/contact', [ClientController::class, 'ContactUs']);
    Route::post('/register', [ClientController::class, 'ClientRegister']);
    Route::post('/login', [ClientController::class, 'ClientLogin']);
    Route::get('/resendverification', [ClientController::class, 'ResendVerificationCode']);
    Route::post('/verify/code', [ClientController::class, 'VerifyCode']);
    Route::post('/password/forget', [ClientController::class, 'ForgetPassword']);
    Route::post('/password/forget/change', [ClientController::class, 'ChangePasswordForget']);
    Route::post('/profile/complete', [ClientController::class, 'CompleteProfile']);
    Route::get('/profile/delete', [ClientController::class, 'ClientDeleteProfile']);
    Route::get('/profile', [ClientController::class, 'ClientProfile']);
    Route::get('/document/remove/{id}', [ClientController::class, 'RemoveClientDocument']);
    Route::post('/profile/update', [ClientController::class, 'UpdateProfile']);
    Route::post('/password/change', [ClientController::class, 'ChangePassword']);
    Route::get('/logout', [ClientController::class, 'ClientLogout']);
    Route::post('/language/change', [ClientController::class, 'ChangeLanguage']);
    Route::post('/securitycode/change', [ClientController::class, 'UpdateSecurityCode']);

    Route::get('/nationalities', [ClientController::class, 'Nationalities']);
    Route::get('/countries', [ClientController::class, 'Countries']);
    Route::get('/cities/{id}', [ClientController::class, 'Cities']);
    Route::get('/areas/{id}', [ClientController::class, 'Areas']);

    Route::post('/home', [ClientController::class, 'ClientHome']);
    Route::post('/brands', [ClientController::class, 'Brands']);
    Route::post('/categories', [ClientController::class, 'Categories']);
    Route::post('/subcategories', [ClientController::class, 'SubCategories']);
    Route::post('/brands/products', [ClientController::class, 'BrandProducts']);
    Route::get('/brands/page/{id}', [ClientController::class, 'BrandPage']);
    Route::get('/shopping', [ClientController::class, 'Shopping']);


    Route::post('/brands/review/add', [ClientController::class, 'AddBrandReview']);
    Route::post('/brands/products/buy', [ClientController::class, 'BuyBrandProduct']);
    Route::post('/brands/products/history', [ClientController::class, 'MyBrandProducts']);
    Route::post('/brands/contactus', [ClientController::class, 'BrandContactUs']);

    Route::post('/network', [ClientController::class, 'ClientNetwork']);
    Route::post('/network/stats', [ClientController::class, 'ClientNetworkStats']);
    Route::post('/network/table/referral', [ClientController::class, 'ClientNetworkReferralTable']);
    Route::post('/network/table', [ClientController::class, 'ClientNetworkTable']);

    Route::post('/wallet/transfer/check', [ClientController::class, 'ClientTransferCheck']);
    Route::post('/wallet/transfer', [ClientController::class, 'ClientBalanceTransfer']);
    Route::post('/wallet/transfer/history', [ClientController::class, 'ClientTransferHistory']);
    Route::post('/wallet/cheque', [ClientController::class, 'ClientCheques']);
    Route::post('/wallet/ledger', [ClientController::class, 'ClientLedger']);
    Route::post('/wallet/points', [ClientController::class, 'ClientRewardPoints']);
    Route::get('/wallet/points/details/{id}', [ClientController::class, 'ClientRewardPointDetail']);

    Route::post('/friend/add', [ClientController::class, 'AddFriend']);
    Route::post('/friend/status', [ClientController::class, 'FriendStatus']);
    Route::post('/friend/list', [ClientController::class, 'FriendList']);
    Route::get('/friend/profile/{id}', [ClientController::class, 'FriendProfile']);

    Route::post('/event', [ClientController::class, 'EventList']);
    Route::get('/event/details/{id}', [ClientController::class, 'EventDetails']);
    Route::post('/event/pay', [ClientController::class, 'EventPay']);

    Route::post('/tool', [ClientController::class, 'ToolList']);
    Route::get('/tool/details/{id}', [ClientController::class, 'ToolDetails']);
    Route::post('/tool/buy', [ClientController::class, 'ToolBuy']);

    Route::post('/plan/products', [ClientController::class, 'PlanProducts']);
    Route::get('/plan/products/details/{id}', [ClientController::class, 'PlanProductDetails']);
    Route::post('/plan/products/buy', [ClientController::class, 'BuyPlanProduct']);
    Route::post('/plan/products/upgrade', [ClientController::class, 'PlanProductUpgrades']);
    Route::post('/plan/products/upgrade/buy', [ClientController::class, 'PlanProductUpgradeBuy']);
    Route::post('/plan/products/history', [ClientController::class, 'PlanProductHistory']);
    Route::get('/plan/network/agencies', [ClientController::class, 'PlanNetworkAgencies']);
    Route::post('/plan/network/agencies/edit', [ClientController::class, 'PlanNetworkAgencyEdit']);

    Route::post('/chat', [ClientController::class, 'ClientChatList']);
    Route::post('/admin/chat', [ClientController::class, 'ClientAdminChat']);
    Route::post('/admin/chat/details', [ClientController::class, 'ClientAdminChatDetails']);
    Route::post('/chat/details', [ClientController::class, 'ClientChatDetails']);
    Route::post('/chat/send', [ClientController::class, 'ClientChatSend']);
    Route::post('/chat/all/send', [ClientController::class, 'ClientChatAllSend']);

    Route::post('/test', [ClientController::class, 'Test']);
    Route::get('/generate-pdf/{client_id}', [ClientController::class, 'generatePdf']);

    Route::post('/send-notifications', [NotificationController::class, 'sendNotifications']);

    Route::get('/all-noification/{client_id}', [NotificationController::class, 'allNoification']);
});
