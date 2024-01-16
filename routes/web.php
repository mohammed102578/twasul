<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('booking/service/cron', 'CronController@service')->name('service.cron');
Route::get('job/hire/cron', 'CronController@job')->name('job.cron');



Route::namespace('Gateway')->prefix('ipn')->name('ipn.')->group(function () {
    Route::post('paypal', 'Paypal\ProcessController@ipn')->name('Paypal');
    Route::get('paypal-sdk', 'PaypalSdk\ProcessController@ipn')->name('PaypalSdk');
    Route::post('perfect-money', 'PerfectMoney\ProcessController@ipn')->name('PerfectMoney');
    Route::post('stripe', 'Stripe\ProcessController@ipn')->name('Stripe');
    Route::post('stripe-js', 'StripeJs\ProcessController@ipn')->name('StripeJs');
    Route::post('stripe-v3', 'StripeV3\ProcessController@ipn')->name('StripeV3');
    Route::post('skrill', 'Skrill\ProcessController@ipn')->name('Skrill');
    Route::post('paytm', 'Paytm\ProcessController@ipn')->name('Paytm');
    Route::post('payeer', 'Payeer\ProcessController@ipn')->name('Payeer');
    Route::post('paystack', 'Paystack\ProcessController@ipn')->name('Paystack');
    Route::post('voguepay', 'Voguepay\ProcessController@ipn')->name('Voguepay');
    Route::get('flutterwave/{trx}/{type}', 'Flutterwave\ProcessController@ipn')->name('Flutterwave');
    Route::post('razorpay', 'Razorpay\ProcessController@ipn')->name('Razorpay');
    Route::post('instamojo', 'Instamojo\ProcessController@ipn')->name('Instamojo');
    Route::get('blockchain', 'Blockchain\ProcessController@ipn')->name('Blockchain');
    Route::get('blockio', 'Blockio\ProcessController@ipn')->name('Blockio');
    Route::post('coinpayments', 'Coinpayments\ProcessController@ipn')->name('Coinpayments');
    Route::post('coinpayments-fiat', 'Coinpayments_fiat\ProcessController@ipn')->name('CoinpaymentsFiat');
    Route::post('coingate', 'Coingate\ProcessController@ipn')->name('Coingate');
    Route::post('coinbase-commerce', 'CoinbaseCommerce\ProcessController@ipn')->name('CoinbaseCommerce');
    Route::get('mollie', 'Mollie\ProcessController@ipn')->name('Mollie');
    Route::post('cashmaal', 'Cashmaal\ProcessController@ipn')->name('Cashmaal');
});


// User Support Ticket
Route::prefix('dashboard')->group(function () {
    Route::prefix('ticket')->group(function () {
        Route::get('/', 'TicketController@supportTicket')->name('ticket');
        Route::get('/new', 'TicketController@openSupportTicket')->name('ticket.open');
        Route::post('/create', 'TicketController@storeSupportTicket')->name('ticket.store');
        Route::get('/view/{ticket}', 'TicketController@viewTicket')->name('ticket.view');
        Route::post('/reply/{ticket}', 'TicketController@replyTicket')->name('ticket.reply');
        Route::get('/download/{ticket}', 'TicketController@ticketDownload')->name('ticket.download');
    });
    Route::get('user_chat/{id}', 'ConversationController@viewMessage');
    Route::get('dashboard/send_message', 'ConversationController@OpenSendMessages');
    Route::post('dashboard/send/message', 'ConversationController@SendSms')->name('message.store');
});



/*
|--------------------------------------------------------------------------
| Start Admin Area
|--------------------------------------------------------------------------
*/

Route::namespace('Admin')->prefix('admin')->name('admin.')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::get('/', 'LoginController@showLoginForm')->name('login');
        Route::post('/', 'LoginController@login')->name('login');
        Route::get('logout', 'LoginController@logout')->name('logout');
        // Admin Password Reset
        Route::get('password/reset', 'ForgotPasswordController@showLinkRequestForm')->name('password.reset');
        Route::post('password/reset', 'ForgotPasswordController@sendResetCodeEmail');
        Route::post('password/verify-code', 'ForgotPasswordController@verifyCode')->name('password.verify.code');
        Route::get('password/reset/{token}', 'ResetPasswordController@showResetForm')->name('password.reset.form');
        Route::post('password/reset/change', 'ResetPasswordController@reset')->name('password.change');
    });

    Route::middleware('admin')->group(function () {

        Route::get('profile', 'AdminController@profile')->name('profile');
        Route::post('profile', 'AdminController@profileUpdate')->name('profile.update');
        Route::get('password', 'AdminController@password')->name('password');
        Route::post('password', 'AdminController@passwordUpdate')->name('password.update');

        Route::middleware('staffaccess:1')->group(function () {
            Route::get('dashboard', 'AdminController@dashboard')->name('dashboard');


            //Notification
            Route::get('notifications', 'AdminController@notifications')->name('notifications');
            Route::get('notification/read/{id}', 'AdminController@notificationRead')->name('notification.read');
            Route::get('notifications/read-all', 'AdminController@readAll')->name('notifications.readAll');
        });

        Route::middleware('staffaccess:29')->group(function () {
            Route::get('system-info', 'AdminController@systemInfo')->name('system.info');
        });

        Route::middleware('staffaccess:32')->group(function () {
            //Report Bugs
            Route::get('request-report', 'AdminController@requestReport')->name('request.report');
            Route::post('request-report', 'AdminController@reportSubmit');
        });

        Route::middleware('staffaccess:9')->group(function () {
            // Users Manager
            Route::get('users', 'ManageUsersController@allUsers')->name('users.all');
            Route::get('users/active', 'ManageUsersController@activeUsers')->name('users.active');
            Route::get('users/banned', 'ManageUsersController@bannedUsers')->name('users.banned');
            Route::get('users/email-verified', 'ManageUsersController@emailVerifiedUsers')->name('users.email.verified');
            Route::get('users/email-unverified', 'ManageUsersController@emailUnverifiedUsers')->name('users.email.unverified');
            Route::get('users/sms-unverified', 'ManageUsersController@smsUnverifiedUsers')->name('users.sms.unverified');
            Route::get('users/sms-verified', 'ManageUsersController@smsVerifiedUsers')->name('users.sms.verified');
            Route::get('users/with-balance', 'ManageUsersController@usersWithBalance')->name('users.with.balance');

            Route::get('users/{scope}/search', 'ManageUsersController@search')->name('users.search');
            Route::get('user/detail/{id}', 'ManageUsersController@detail')->name('users.detail');
            Route::post('user/update/{id}', 'ManageUsersController@update')->name('users.update');
            Route::post('user/add-sub-balance/{id}', 'ManageUsersController@addSubBalance')->name('users.add.sub.balance');
            Route::get('user/send-email/{id}', 'ManageUsersController@showEmailSingleForm')->name('users.email.single');
            Route::post('user/send-email/{id}', 'ManageUsersController@sendEmailSingle')->name('users.email.single');
            Route::get('user/login/{id}', 'ManageUsersController@login')->name('users.login');
            Route::get('user/transactions/{id}', 'ManageUsersController@transactions')->name('users.transactions');
            Route::get('user/deposits/{id}', 'ManageUsersController@deposits')->name('users.deposits');
            Route::get('user/deposits/via/{method}/{type?}/{userId}', 'ManageUsersController@depositViaMethod')->name('users.deposits.method');
            Route::get('user/withdrawals/{id}', 'ManageUsersController@withdrawals')->name('users.withdrawals');
            Route::get('user/withdrawals/via/{method}/{type?}/{userId}', 'ManageUsersController@withdrawalsViaMethod')->name('users.withdrawals.method');
            // Login History
            Route::get('users/login/history/{id}', 'ManageUsersController@userLoginHistory')->name('users.login.history.single');

            Route::get('referrals/{id}', 'ManageUsersController@referrals')->name('users.referrals');

            Route::get('users/send-email', 'ManageUsersController@showEmailAllForm')->name('users.email.all');
            Route::post('users/send-email', 'ManageUsersController@sendEmailAll')->name('users.email.send');
            Route::get('users/email-log/{id}', 'ManageUsersController@emailLog')->name('users.email.log');
            Route::get('users/email-details/{id}', 'ManageUsersController@emailDetails')->name('users.email.details');
            Route::get('users/service/{id}', 'ManageUsersController@userService')->name('users.service');
            Route::get('users/product/{id}', 'ManageUsersController@userSoftware')->name('users.software');
            Route::get('users/job/{id}', 'ManageUsersController@userJob')->name('users.job');
            Route::get('users/service/booking/{id}', 'ManageUsersController@userServiceBooking')->name('users.service.booking');
            Route::get('users/product/purchases/{id}', 'ManageUsersController@userSoftwareBuy')->name('users.software.purchases');
        });

        Route::middleware('staffaccess:18')->group(function () {
            // Subscriber
            Route::get('subscriber', 'SubscriberController@index')->name('subscriber.index');
            Route::get('subscriber/send-email', 'SubscriberController@sendEmailForm')->name('subscriber.sendEmail');
            Route::post('subscriber/remove', 'SubscriberController@remove')->name('subscriber.remove');
            Route::post('subscriber/send-email', 'SubscriberController@sendEmail')->name('subscriber.sendEmail');
        });

        Route::middleware('staffaccess:5')->group(function () {
            //Manage Service
            Route::get('service', 'ServiceController@index')->name('service.index');
            Route::get('service/details/{id}', 'ServiceController@details')->name('service.details');
            Route::get('service/pending', 'ServiceController@pending')->name('service.pending');
            Route::get('service/approved', 'ServiceController@approved')->name('service.approved');
            Route::get('service/cancel', 'ServiceController@cancel')->name('service.cancel');
            Route::post('service/featured/include', 'ServiceController@featuredInclude')->name('service.featured.include');
            Route::post('service/featured/remove', 'ServiceController@featuredNotInclude')->name('service.featured.remove');
            Route::post('service/approvedBy', 'ServiceController@approvedBy')->name('service.approvedBy');
            Route::post('service/cancelBy', 'ServiceController@cancelBy')->name('service.cancelBy');
            Route::get('service/category', 'ServiceController@serviceCategory')->name('service.category');
            Route::get('service/{scope}/search', 'ServiceController@search')->name('service.search');
        });

        Route::middleware('staffaccess:6')->group(function () {
            //Manage Software
            Route::get('product/index', 'SoftwareController@index')->name('software.index');
            Route::get('product/pending', 'SoftwareController@pending')->name('software.pending');
            Route::get('product/approved', 'SoftwareController@approved')->name('software.approved');
            Route::get('product/cancel', 'SoftwareController@cancel')->name('software.cancel');
            Route::get('product/category', 'SoftwareController@softwareCategory')->name('software.category');
            Route::get('product/detail/{id}', 'SoftwareController@details')->name('software.details');
            Route::post('product/approvedBy', 'SoftwareController@approvedBy')->name('software.approvedBy');
            Route::post('product/cancelBy', 'SoftwareController@cancelBy')->name('software.cancelBy');
            Route::get('product/{scope}/search', 'SoftwareController@search')->name('software.search');
            Route::get('product/download/{id}', 'SoftwareController@softwareFile')->name('software.download');
            Route::get('product/document/{id}', 'SoftwareController@softwareDocument')->name('document.download');

            Route::get('product/indexadmin', 'SoftwareController@indexadmin')->name('software.indexadmin');
            Route::get('product/detailadmin/{id}', 'SoftwareController@detailsadmin')->name('software.detailsadmin');
            Route::get('product/createadminproduct', 'SoftwareController@createadminproduct')->name('software.createadminproduct');
            Route::post('product/store', 'SoftwareController@store')->name('software.store');
            Route::get('/product/edit/{slug}/{id}', 'SoftwareController@edit')->name('software.edit');
            Route::post('/product/update/{id}', 'SoftwareController@update')->name('software.update');
            Route::get('/product/manage/{slug}/{id}', 'SoftwareController@manageProduct')->name('software.manage');
            Route::post('/product/updatemanage/{id}', 'SoftwareController@updateManage')->name('software.updateManage');
            Route::post('/product/delete', 'SoftwareController@delete')->name('software.delete');

           /* Route::get('product/get_wp_products', function () {
                Artisan::call('get_products');
            })->name('software.get_wp_products');*/


        });

        Route::middleware('staffaccess:7')->group(function () {
            //Manage Job
            Route::get('job/index', 'JobController@index')->name('job.index');
            Route::get('job/closed', 'JobController@closed')->name('job.closed');
            Route::get('job/pending', 'JobController@pending')->name('job.pending');
            Route::get('job/approved', 'JobController@approved')->name('job.approved');
            Route::get('job/cancel', 'JobController@cancel')->name('job.cancel');
            Route::post('job/cancelBy', 'JobController@cancelBy')->name('job.cancelBy');
            Route::post('job/approvedBy', 'JobController@approvedBy')->name('job.approvedBy');
            Route::post('job/closedBy', 'JobController@closedBy')->name('job.closedBy');
            Route::get('job/details/{id}', 'JobController@details')->name('job.details');
            Route::get('job/biding/list/{id}', 'JobController@jobBiding')->name('job.biding.list');
            Route::get('job/biding/details/{id}', 'JobController@jobBidingDetails')->name('job.biding.details');
            Route::get('job/category', 'JobController@jobCategory')->name('job.category');
            Route::get('job/{scope}/search', 'JobController@search')->name('job.search');
        });

        Route::middleware('staffaccess:34')->group(function () {
            // Manage Staff
            Route::get('staff/index', 'StaffController@index')->name('staff.index');
            Route::get('staff/create', 'StaffController@create')->name('staff.create');
            Route::post('staff/store', 'StaffController@store')->name('staff.store');
            Route::get('staff/edit/{id}', 'StaffController@edit')->name('staff.edit');
            Route::post('staff/update/{id}', 'StaffController@update')->name('staff.update');
            Route::post('staff/delete/', 'StaffController@delete')->name('staff.delete');
        });

        Route::middleware('staffaccess:13')->group(function () {
            // Deposit Gateway
            Route::name('gateway.')->prefix('gateway')->group(function () {
                // Automatic Gateway
                Route::get('automatic', 'GatewayController@index')->name('automatic.index');
                Route::get('automatic/edit/{alias}', 'GatewayController@edit')->name('automatic.edit');
                Route::post('automatic/update/{code}', 'GatewayController@update')->name('automatic.update');
                Route::post('automatic/remove/{code}', 'GatewayController@remove')->name('automatic.remove');
                Route::post('automatic/activate', 'GatewayController@activate')->name('automatic.activate');
                Route::post('automatic/deactivate', 'GatewayController@deactivate')->name('automatic.deactivate');


                // Manual Methods
                Route::get('manual', 'ManualGatewayController@index')->name('manual.index');
                Route::get('manual/new', 'ManualGatewayController@create')->name('manual.create');
                Route::post('manual/new', 'ManualGatewayController@store')->name('manual.store');
                Route::get('manual/edit/{alias}', 'ManualGatewayController@edit')->name('manual.edit');
                Route::post('manual/update/{id}', 'ManualGatewayController@update')->name('manual.update');
                Route::post('manual/activate', 'ManualGatewayController@activate')->name('manual.activate');
                Route::post('manual/deactivate', 'ManualGatewayController@deactivate')->name('manual.deactivate');
            });
        });

        Route::middleware('staffaccess:14')->group(function () {
            // DEPOSIT SYSTEM
            Route::name('deposit.')->prefix('deposit')->group(function () {
                Route::get('/', 'DepositController@deposit')->name('list');
                Route::get('pending', 'DepositController@pending')->name('pending');
                Route::get('rejected', 'DepositController@rejected')->name('rejected');
                Route::get('approved', 'DepositController@approved')->name('approved');
                Route::get('successful', 'DepositController@successful')->name('successful');
                Route::get('details/{id}', 'DepositController@details')->name('details');

                Route::post('reject', 'DepositController@reject')->name('reject');
                Route::post('approve', 'DepositController@approve')->name('approve');
                Route::get('via/{method}/{type?}', 'DepositController@depositViaMethod')->name('method');
                Route::get('/{scope}/search', 'DepositController@search')->name('search');
                Route::get('date-search/{scope}', 'DepositController@dateSearch')->name('dateSearch');
            });
        });

        Route::middleware('staffaccess:15')->group(function () {
            // WITHDRAW SYSTEM
            Route::name('withdraw.')->prefix('withdraw')->group(function () {
                Route::get('pending', 'WithdrawalController@pending')->name('pending');
                Route::get('approved', 'WithdrawalController@approved')->name('approved');
                Route::get('rejected', 'WithdrawalController@rejected')->name('rejected');
                Route::get('log', 'WithdrawalController@log')->name('log');
                Route::get('via/{method_id}/{type?}', 'WithdrawalController@logViaMethod')->name('method');
                Route::get('{scope}/search', 'WithdrawalController@search')->name('search');
                Route::get('date-search/{scope}', 'WithdrawalController@dateSearch')->name('dateSearch');
                Route::get('details/{id}', 'WithdrawalController@details')->name('details');
                Route::post('approve', 'WithdrawalController@approve')->name('approve');
                Route::post('reject', 'WithdrawalController@reject')->name('reject');


                // Withdraw Method
                Route::get('method/', 'WithdrawMethodController@methods')->name('method.index');
                Route::get('method/create', 'WithdrawMethodController@create')->name('method.create');
                Route::post('method/create', 'WithdrawMethodController@store')->name('method.store');
                Route::get('method/edit/{id}', 'WithdrawMethodController@edit')->name('method.edit');
                Route::post('method/edit/{id}', 'WithdrawMethodController@update')->name('method.update');
                Route::post('method/activate', 'WithdrawMethodController@activate')->name('method.activate');
                Route::post('method/deactivate', 'WithdrawMethodController@deactivate')->name('method.deactivate');
            });
        });

        Route::middleware('staffaccess:2')->group(function () {
            //Service Booking
            Route::get('service/booking', 'ServiceBookingController@index')->name('booking.service.index');
            Route::get('service/booking/details/{id}', 'ServiceBookingController@details')->name('booking.service.details');
            Route::get('service/booking/pending', 'ServiceBookingController@pending')->name('booking.service.pending');
            Route::get('service/booking/completed', 'ServiceBookingController@completed')->name('booking.service.completed');
            Route::get('service/booking/delivered', 'ServiceBookingController@delivered')->name('booking.service.delivered');
            Route::get('service/booking/inprogress', 'ServiceBookingController@inProgress')->name('booking.service.inProgress');
            Route::get('service/booking/dispute', 'ServiceBookingController@dispute')->name('booking.service.dispute');
            Route::get('service/booking/expired', 'ServiceBookingController@expired')->name('booking.service.expired');
            Route::get('service/booking/cacnel', 'ServiceBookingController@cacnel')->name('booking.service.cacnel');
            Route::post('service/booking/payment', 'ServiceBookingController@payment')->name('booking.service.payment');
            Route::get('service/booking/{scope}/search', 'ServiceBookingController@search')->name('booking.service.search');
            Route::get('service/booking/work/delivery/{id}', 'ServiceBookingController@workDeliveryDownload')->name('work.delivery.download');
        });

        Route::middleware('staffaccess:3')->group(function () {
            // Sales Software
            Route::get('product/sales', 'BuySoftwareController@index')->name('sales.software.index');
            Route::get('product/sales/pending', 'BuySoftwareController@pending')->name('sales.software.pending');
            Route::get('product/sales/shipping', 'BuySoftwareController@shipping')->name('sales.software.shipping');
            Route::get('product/sales/completed', 'BuySoftwareController@completed')->name('sales.software.completed');
            Route::get('product/sales/rejected', 'BuySoftwareController@rejected')->name('sales.software.rejected');
            Route::get('product/sales/admin', 'BuySoftwareController@adminSales')->name('sales.software.admin');
            Route::post('product/sales/status', 'BuySoftwareController@updateStatus')->name('sales.software.updateStatus');
            Route::get('product/file/download/{id}', 'BuySoftwareController@softwareFileDownload')->name('software.file.download');
            Route::get('product/document/download/{id}', 'BuySoftwareController@softwareDocumentFile')->name('software.document.download');
            Route::get('sales/product/search', 'BuySoftwareController@search')->name('sales.software.search');
        });

        Route::middleware('staffaccess:4')->group(function () {
            // Hire Employ
            Route::get('hire/employees', 'HireEmployController@index')->name('hire.index');
            Route::get('work/completed', 'HireEmployController@completed')->name('hire.completed');
            Route::get('work/delivered', 'HireEmployController@delivered')->name('hire.delivered');
            Route::get('work/inprogress', 'HireEmployController@inprogress')->name('hire.inprogress');
            Route::get('work/expired', 'HireEmployController@expired')->name('hire.expired');
            Route::get('work/dispute', 'HireEmployController@dispute')->name('hire.dispute');
            Route::get('hire/employees/details/{id}', 'HireEmployController@details')->name('hire.details');
            Route::get('hire/file/download/{id}', 'HireEmployController@workFileDownload')->name('hire.work.file.download');
            Route::post('employees/payment', 'HireEmployController@payment')->name('employ.payment');
            Route::get('hire/employees/{scope}/search', 'HireEmployController@search')->name('hire.search');
        });

        Route::middleware('staffaccess:33')->group(function () {
            //Rank
            Route::get('rank', 'RankController@index')->name('rank.index');
            Route::post('rank/store', 'RankController@store')->name('rank.store');
            Route::post('rank/update', 'RankController@update')->name('rank.update');
        });

        Route::middleware('staffaccess:10')->group(function () {
            // Coupon
            Route::get('coupon', 'CouponController@index')->name('coupon.index');
            Route::post('coupon/store', 'CouponController@store')->name('coupon.store');
            Route::post('coupon/update', 'CouponController@update')->name('coupon.update');
            Route::post('coupon/delete', 'CouponController@delete')->name('coupon.delete');
        });
        Route::middleware('staffaccess:35')->group(function () {
            // Reward
            Route::get('reward', 'RewardController@index')->name('reward.index');
            Route::get('reward/review', 'RewardController@review')->name('reward.review');
            Route::post('reward/review/update', 'RewardController@review_update')->name('reward.review.update');

            Route::post('reward/store', 'RewardController@store')->name('reward.store');
            Route::post('reward/update', 'RewardController@update')->name('reward.update');
            Route::post('reward/delete', 'RewardController@delete')->name('reward.delete');
        });

        Route::middleware('staffaccess:11')->group(function () {
            //Category
            Route::get('category', 'CategoryController@index')->name('category.index');
            Route::post('category/store', 'CategoryController@store')->name('category.store');
            Route::post('category/update', 'CategoryController@update')->name('category.update');
            Route::get('sub/category', 'CategoryController@subCategoryIndex')->name('category.subcategory.index');
            Route::post('sub/category/store', 'CategoryController@subCategoryStore')->name('category.subcategory.store');
            Route::post('sub/category/update', 'CategoryController@subCategoryUpdate')->name('category.subcategory.update');
        });

        Route::middleware('staffaccess:12')->group(function () {
            //features
            // Route::get('features', 'FeaturesController@index')->name('features.index');
            Route::post('features/store', 'FeaturesController@store')->name('features.store');
            Route::post('features/update', 'FeaturesController@update')->name('features.update');
        });

        Route::middleware('staffaccess:8')->group(function () {
            // Advertisement
            Route::get('ads/index', 'AdvertisementController@index')->name('ads.index');
            Route::post('ads/store', 'AdvertisementController@store')->name('ads.store');
            Route::get('ads/edit/{id}', 'AdvertisementController@edit')->name('ads.edit');
            Route::post('ads/update/{id}', 'AdvertisementController@update')->name('ads.update');
            Route::post('ads/delete', 'AdvertisementController@delete')->name('ads.delete');
        });

        Route::middleware('staffaccess:17')->group(function () {
            // Report
            Route::get('report/transaction', 'ReportController@transaction')->name('report.transaction');
            Route::get('report/transaction/search', 'ReportController@transactionSearch')->name('report.transaction.search');
            Route::get('report/login/history', 'ReportController@loginHistory')->name('report.login.history');
            Route::get('report/login/ipHistory/{ip}', 'ReportController@loginIpHistory')->name('report.login.ipHistory');
            Route::get('report/email/history', 'ReportController@emailHistory')->name('report.email.history');
        });

        Route::middleware('staffaccess:16')->group(function () {
            // Admin Support
            Route::get('tickets', 'SupportTicketController@tickets')->name('ticket');
            Route::get('tickets/pending', 'SupportTicketController@pendingTicket')->name('ticket.pending');
            Route::get('tickets/closed', 'SupportTicketController@closedTicket')->name('ticket.closed');
            Route::get('tickets/answered', 'SupportTicketController@answeredTicket')->name('ticket.answered');
            Route::get('tickets/view/{id}', 'SupportTicketController@ticketReply')->name('ticket.view');
            Route::post('ticket/reply/{id}', 'SupportTicketController@ticketReplySend')->name('ticket.reply');
            Route::get('ticket/download/{ticket}', 'SupportTicketController@ticketDownload')->name('ticket.download');
            Route::post('ticket/delete', 'SupportTicketController@ticketDelete')->name('ticket.delete');


            //Admin Support Chat
             Route::get('chats', 'ConversationController@chats')->name('chat');
             Route::get('/pending', 'ConversationController@pending_chats')->name('pending_chats');
             Route::get('/answered', 'ConversationController@answered_chats')->name('answered_chats');
            Route::get('/chats/view/{id}', 'ConversationController@chatsReply')->name('chat_view');
            Route::post('/chats/delete', 'ConversationController@delete_chats')->name('delete_chats');
        });

        Route::middleware('staffaccess:22')->group(function () {
            // Language Manager
            Route::get('/language', 'LanguageController@langManage')->name('language.manage');
            Route::post('/language', 'LanguageController@langStore')->name('language.manage.store');
            Route::post('/language/delete/{id}', 'LanguageController@langDel')->name('language.manage.del');
            Route::post('/language/update/{id}', 'LanguageController@langUpdate')->name('language.manage.update');
            Route::get('/language/edit/{id}', 'LanguageController@langEdit')->name('language.key');
            Route::post('/language/import', 'LanguageController@langImport')->name('language.importLang');

            Route::post('language/store/key/{id}', 'LanguageController@storeLanguageJson')->name('language.store.key');
            Route::post('language/delete/key/{id}', 'LanguageController@deleteLanguageJson')->name('language.delete.key');
            Route::post('language/update/key/{id}', 'LanguageController@updateLanguageJson')->name('language.update.key');
        });




        Route::middleware('staffaccess:19')->group(function () {

            // General Setting
            Route::get('general-setting', 'GeneralSettingController@index')->name('setting.index');
            Route::post('general-setting', 'GeneralSettingController@update')->name('setting.update');
        });

        Route::middleware('staffaccess:31')->group(function () {
            Route::get('optimize', 'GeneralSettingController@optimize')->name('setting.optimize');
        });

        Route::middleware('staffaccess:20')->group(function () {
            // Logo-Icon
            Route::get('setting/logo-icon', 'GeneralSettingController@logoIcon')->name('setting.logo.icon');
            Route::post('setting/logo-icon', 'GeneralSettingController@logoIconUpdate')->name('setting.logo.icon');
        });

        Route::middleware('staffaccess:30')->group(function () {
            //Custom CSS
            Route::get('custom-css', 'GeneralSettingController@customCss')->name('setting.custom.css');
            Route::post('custom-css', 'GeneralSettingController@customCssSubmit');
        });

        Route::middleware('staffaccess:26')->group(function () {
            //Cookie
            Route::get('cookie', 'GeneralSettingController@cookie')->name('setting.cookie');
            Route::post('cookie', 'GeneralSettingController@cookieSubmit');
        });

        Route::middleware('staffaccess:21')->group(function () {
            // Plugin
            Route::get('extensions', 'ExtensionController@index')->name('extensions.index');
            Route::post('extensions/update/{id}', 'ExtensionController@update')->name('extensions.update');
            Route::post('extensions/activate', 'ExtensionController@activate')->name('extensions.activate');
            Route::post('extensions/deactivate', 'ExtensionController@deactivate')->name('extensions.deactivate');
        });


        Route::middleware('staffaccess:24')->group(function () {
            // Email Setting
            Route::get('email-template/global', 'EmailTemplateController@emailTemplate')->name('email.template.global');
            Route::post('email-template/global', 'EmailTemplateController@emailTemplateUpdate')->name('email.template.global');
            Route::get('email-template/setting', 'EmailTemplateController@emailSetting')->name('email.template.setting');
            Route::post('email-template/setting', 'EmailTemplateController@emailSettingUpdate')->name('email.template.setting');
            Route::get('email-template/index', 'EmailTemplateController@index')->name('email.template.index');
            Route::get('email-template/{id}/edit', 'EmailTemplateController@edit')->name('email.template.edit');
            Route::post('email-template/{id}/update', 'EmailTemplateController@update')->name('email.template.update');
            Route::post('email-template/send-test-mail', 'EmailTemplateController@sendTestMail')->name('email.template.test.mail');
        });

        Route::middleware('staffaccess:25')->group(function () {
            // SMS Setting
            Route::get('sms-template/global', 'SmsTemplateController@smsTemplate')->name('sms.template.global');
            Route::post('sms-template/global', 'SmsTemplateController@smsTemplateUpdate')->name('sms.template.global');
            Route::get('sms-template/setting', 'SmsTemplateController@smsSetting')->name('sms.templates.setting');
            Route::post('sms-template/setting', 'SmsTemplateController@smsSettingUpdate')->name('sms.template.setting');
            Route::get('sms-template/index', 'SmsTemplateController@index')->name('sms.template.index');
            Route::get('sms-template/edit/{id}', 'SmsTemplateController@edit')->name('sms.template.edit');
            Route::post('sms-template/update/{id}', 'SmsTemplateController@update')->name('sms.template.update');
            Route::post('email-template/send-test-sms', 'SmsTemplateController@sendTestSMS')->name('sms.template.test.sms');
        });

        Route::middleware('staffaccess:23')->group(function () {
            // SEO
            Route::get('seo', 'FrontendController@seoEdit')->name('seo');
        });


        Route::middleware('staffaccess:27')->group(function () {
            Route::name('frontend.')->prefix('frontend')->group(function () {
                Route::get('templates', 'FrontendController@templates')->name('templates');
                Route::post('templates', 'FrontendController@templatesActive')->name('templates.active');
            });
        });
        Route::middleware('staffaccess:28')->group(function () {
            // Frontend
            Route::name('frontend.')->prefix('frontend')->group(function () {
                Route::get('frontend-sections/{key}/{status}', 'FrontendController@frontendSections')->name('sections');
                Route::post('frontend-content/{key}', 'FrontendController@frontendContent')->name('sections.content');
                Route::get('frontend-element/{key}/{id?}', 'FrontendController@frontendElement')->name('sections.element');
                Route::get('get-subcategory/{id}', 'FrontendController@getSubcategory')->name('sections.get-subcategory');
                Route::post('remove', 'FrontendController@remove')->name('remove');
                Route::post('approve', 'FrontendController@approve')->name('approve');
                Route::post('reject', 'FrontendController@reject')->name('reject');
            });
        });
    });
});




/*
|--------------------------------------------------------------------------
| Start User Area
|--------------------------------------------------------------------------
*/
  

Route::name('user.')->group(function () {
    Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('/login', 'Auth\LoginController@login');
    Route::get('logout', 'Auth\LoginController@logout')->name('logout');

    Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
    Route::post('register', 'Auth\RegisterController@register')->middleware('regStatus');
    Route::post('check-mail', 'Auth\RegisterController@checkUser')->name('checkUser');

    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetCodeEmail')->name('password.email');
    Route::get('password/code-verify', 'Auth\ForgotPasswordController@codeVerify')->name('password.code.verify');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
    Route::post('password/verify-code', 'Auth\ForgotPasswordController@verifyCode')->name('password.verify.code');


});
Route::middleware('auth')->group(function () {
    Route::get('dashboard', 'Seller\UserController@home')->name('user.home');
});
// Route::get('dashboard1', 'Buyer\HomeController@index')->name('user.home');

Route::name('user.')->group(function () {

    Route::middleware('auth')->group(function () {
        Route::get('authorization', 'AuthorizationController@authorizeForm')->name('authorization');
        Route::get('resend-verify', 'AuthorizationController@sendVerifyCode')->name('send.verify.code');
        Route::post('verify-email', 'AuthorizationController@emailVerification')->name('verify.email');
        Route::post('verify-sms', 'AuthorizationController@smsVerification')->name('verify.sms');
        Route::post('verify-g2fa', 'AuthorizationController@g2faVerification')->name('go2fa.verify');
        Route::get('switch-seller', 'AuthorizationController@switchSeller')->name('switch-seller');
        Route::get('switch-buyer', 'AuthorizationController@switchBuyer')->name('switch-buyer');


        Route::middleware(['checkStatus'])->group(function () {
            Route::namespace('Seller')->prefix('dashboard')->group(function () {
                Route::get('blogs/{key}', 'FrontendController@frontendSections')->name('sections');
                // Route::post('blogs-store/{key}', 'FrontendController@frontendContent')->name('sections.content');
                // Route::get('dashboard', 'UserController@home')->name('home');
                Route::get('blogs', 'BlogController@index')->name('blog');
                Route::get('get-subcategory/{id}', 'BlogController@getSubcategory')->name('get-subcategory');
                Route::get('blogs-create', 'BlogController@create')->name('blog-create');
                Route::post('blogs-store', 'BlogController@store')->name('blog-store');
                Route::get('blogs-edit/{id}', 'BlogController@edit')->name('blog-edit');
                Route::post('blogs-remove', 'BlogController@destroy')->name('blog-remove');
                Route::get('profile-setting', 'UserController@profile')->name('profile.setting');
                Route::post('profile-setting', 'UserController@submitProfile');
                Route::get('change-password', 'UserController@changePassword')->name('change.password');
                Route::post('change-password', 'UserController@submitPassword');
                Route::get('invited-users', 'UserController@invitedUsers')->name('invited.users');

                //Rewards
                Route::get('rewards', 'RewardController@rewards')->name('rewards');
                Route::post('request-review-submit', 'RewardController@request_review_submit')->name('request.review.rewards');


                //2FA
                Route::get('twofactor', 'UserController@show2faForm')->name('twofactor');
                Route::post('twofactor/enable', 'UserController@create2fa')->name('twofactor.enable');
                Route::post('twofactor/disable', 'UserController@disable2fa')->name('twofactor.disable');

                // Withdraw
                Route::get('/withdraw', 'UserController@withdrawMoney')->name('withdraw');
                Route::post('/withdraw', 'UserController@withdrawStore')->name('withdraw.money');
                Route::get('/withdraw/preview', 'UserController@withdrawPreview')->name('withdraw.preview');
                Route::post('/withdraw/preview', 'UserController@withdrawSubmit')->name('withdraw.submit');
                Route::get('/withdraw/history', 'UserController@withdrawLog')->name('withdraw.history');
                
                //payment
                Route::get('/payment', 'UserController@payment')->name('payment');


                // History
                Route::get('/payment/history', 'UserController@history')->name('payment.history');;

                //Home Controller
                Route::get('service/booking/', 'HomeController@serviceBookeds')->name('booking.service');
                Route::get('service/booking/details/{id}', 'HomeController@serviceBookingDetails')->name('booking.service.details');
                Route::post('booking/confirm', 'HomeController@bookingConfirm')->name('booking.confirm');
                Route::post('work/upload', 'HomeController@workUpload')->name('work.upload');
                Route::get('work/file/download/{id}', 'HomeController@workFileDownload')->name('work.file.download');
                Route::get('/product/sales', 'HomeController@salesSoftware')->name('software.sales');
                Route::get('/job/list', 'HomeController@jobVacancy')->name('job.vacancy');
                Route::get('/job/list/detail/{id}', 'HomeController@jobListDetails')->name('seller.job.list.details');
                Route::get('/follow/{id}', 'HomeController@follow')->name('follow');
                //Route::get('transactions', 'HomeController@transactions')->name('seller.transactions');

                //Service
                Route::get('/service/index', 'ServiceController@index')->name('service.index');
                Route::get('/service/create', 'ServiceController@create')->name('service.create');
                Route::post('/service/store', 'ServiceController@store')->name('service.store');
                Route::post('/service/update/{id}', 'ServiceController@update')->name('service.update');
                Route::get('/service/edit/{slug}/{id}', 'ServiceController@edit')->name('service.edit');
                Route::post('/optional/image', 'ServiceController@optionalImageRemove')->name('optional.image');
                Route::get('/category', 'UserController@category')->name('category');
                Route::post('/favorite/service/', 'UserController@serviceFavorite')->name('favorite.service');
                Route::post('/favorite/product', 'UserController@softwareFavorite')->name('favorite.software');

                //Software
                Route::get('/product', 'SoftwareController@index')->name('software.index');
                Route::get('/product/file/download/{id}', 'SoftwareController@softwareFileDownload')->name('software.file.download');
                Route::get('/product/document/download/{id}', 'SoftwareController@softwareDocumentFile')->name('software.document.download');
                Route::get('/product/upload', 'SoftwareController@create')->name('software.create');
                Route::post('/product/store', 'SoftwareController@store')->name('software.store');
                Route::get('/product/edit/{slug}/{id}', 'SoftwareController@edit')->name('software.edit');
                Route::post('/product/update/{id}', 'SoftwareController@update')->name('software.update');
                Route::post('/product/verity', 'SoftwareController@productVerityRemove')->name('product.verity');
                Route::post('/product/verity-update', 'SoftwareController@productVerityUpdate')->name('product.verity_update');
                Route::get('/product/manage/{slug}/{id}', 'SoftwareController@manageProduct')->name('software.manage');
                Route::post('/product/updatemanage/{id}', 'SoftwareController@updateManage')->name('software.updateManage');
                Route::get('product/booking/details/{id}', 'HomeController@productBookingDetails')->name('booking.product.details');
                Route::post('product-booking/confirm', 'HomeController@productBookingConfirm')->name('product_booking.confirm');
                Route::post('/check_product_code', 'SoftwareController@checkProductCode')->name('seller.checkProductCode');
            });

            Route::any('/deposit', 'Gateway\PaymentController@deposit')->name('deposit');
            Route::post('deposit/insert', 'Gateway\PaymentController@depositInsert')->name('deposit.insert');
            Route::get('deposit/preview', 'Gateway\PaymentController@depositPreview')->name('deposit.preview');
            Route::get('deposit/confirm', 'Gateway\PaymentController@depositConfirm')->name('deposit.confirm');
            Route::get('deposit/manual', 'Gateway\PaymentController@manualDepositConfirm')->name('deposit.manual.confirm');
            Route::post('deposit/manual', 'Gateway\PaymentController@manualDepositUpdate')->name('deposit.manual.update');

            Route::namespace('Buyer')->prefix('dashboard')->group(function () {
                // Route::get('dashboard', 'HomeController@index')->name('buyer.dashboard');
                Route::get('deposit/history', 'HomeController@depositHistory')->name('deposit.history');
                Route::get('transactions', 'HomeController@transactions')->name('buyer.transactions');
                Route::get('work/delivered/download/{id}', 'HomeController@workDeliveryDownload')->name('work.delivery.download');
                //service
                Route::get('service/booked', 'HomeController@serviceBookingItem')->name('buyer.service.booked');
                Route::get('service/booked/details/{id}', 'HomeController@serviceBookingDetails')->name('booked.service.details');
                // Route::get('service/booking/details/{id}', 'HomeController@serviceBookingDetails')->name('buyer.service.booked.details');
                Route::get('favorite/item', 'HomeController@FavoriteItem')->name('favorite');
                
                Route::get('favorite/service/item', 'HomeController@serviceFavoriteItem')->name('service.favorite');
                Route::get('favorite/product/item', 'HomeController@softwareFavoriteItem')->name('software.favorite');
                Route::post('work/delivery/approved', 'HomeController@workDeliveryApproved')->name('work.delivery.approved');
                Route::post('work/dispute', 'HomeController@workDispute')->name('work.dispute');
                //software
                Route::get('product/purchases/list', 'HomeController@softwarePurchases')->name('software.purchases');
                // Route::get('product/file/download/{id}', 'HomeController@buyerSoftwareFileDownload')->name('buyer.software.file.download');
                // Route::get('product/document/download/{id}', 'HomeController@buyerSoftwareDocumentFile')->name('buyer.software.document.download');
                Route::get('hire/employees', 'HomeController@hireEmploy')->name('buyer.hire.employ');
                Route::get('hire/employees/details/{id}', 'HomeController@hireEmployDetails')->name('buyer.hire.employ.details');
                Route::post('product-delivered/confirm', 'HomeController@productDeliveredConfirm')->name('product_delivered.confirm');
                Route::get('product/booked/details/{id}', 'HomeController@productBookedDetails')->name('booked.product.details');
                //Job
                Route::get('job/create', 'JobController@create')->name('job.create');
                Route::post('job/store', 'JobController@store')->name('job.store');
                Route::get('job/index', 'JobController@index')->name('job.index');
                Route::get('job/edit/{slug}/{id}', 'JobController@edit')->name('job.edit');
                Route::post('job/update/{id}', 'JobController@update')->name('job.update');
                Route::post('job/cancel', 'JobController@cancelBy')->name('job.cancel');
            });

            //JobBiding
            Route::post('job/biding', 'JobBidingController@store')->name('job.biding.store');
            //Conversation
            Route::post('conversation', 'ConversationController@store')->name('conversation.store');
            Route::get('dashboard/inbox', 'ConversationController@inbox')->name('conversation.inbox');
            Route::get('chat/{id}', 'ConversationController@chat')->name('conversation.chat');
            Route::post('message/store', 'ConversationController@messageStore')->name('message.store');

            //Comment
            Route::post('comment', 'CommentController@store')->name('comment.store');
            Route::post('comment/reply', 'CommentController@commentReply')->name('comment.reply');

            //Service Booking
            Route::get('service/booking/{slug}/{id}', 'BookingController@serviceBooking')->name('service.booking');
            Route::get('service/coupon/apply', 'BookingController@applyCoupon')->name('service.coupon.apply');
            Route::post('service/booked', 'BookingController@serviceBooked')->name('service.booked');
            Route::get('payment/method', 'BookingController@payment')->name('payment.method');
            Route::post('payment/insert', 'BookingController@paymentInsert')->name('payment.insert');

            //Software Buy
            Route::get('product/buy/{slug}/{id}', 'SoftwareBuyController@softwareBuy')->name('software.buy');
            Route::get('product/coupon/apply', 'SoftwareBuyController@applyCouponSoftware')->name('software.coupon.apply');
            Route::post('product/buy/store', 'SoftwareBuyController@softwareBuyStore')->name('software.buy.store');
            Route::post('product/buy/real', 'SoftwareBuyController@softwareBuyRealProduct')->name('software.buy.real');

            // Job Biding
            Route::get('job/biding/order/{slug}/{id}', 'BidingOrderController@jobBiding')->name('job.biding.order');
            Route::post('hire/employ', 'BidingOrderController@hireEmploy')->name('hire.employ');

            //Review
            Route::post('review', 'ReviewController@store')->name('review.store');
        });
    });
});
Route::middleware('langurl')->group(function () {

    Route::get('/contact', 'SiteController@contact')->name('contact');
    Route::post('/contact', 'SiteController@contactSubmit');
    Route::get('/change/{lang?}', 'SiteController@changeLanguage')->name('lang');
    Route::get('/cookie/accept', 'SiteController@cookieAccept')->name('cookie.accept');
    Route::get('article', 'SiteController@blog')->name('blog');
    Route::get('blog/{id}/{slug}', 'SiteController@blogDetails')->name('blog.details');
    Route::get('placeholder-image/{size}', 'SiteController@placeholderImage')->name('placeholder.image');

    //Service
    Route::get('/', 'SiteController@index')->name('home');
    Route::get('/load-more-data', 'SiteController@loadMoreData')->name('load-more-data');
    Route::get('/service', 'SiteController@service')->name('service');
    Route::get('/service/details/{slug}/{id}', 'SiteController@serviceDetails')->name('service.details');
    Route::get('/search/item/filter', 'FilterController@allServiceSearch')->name('home.search.item');
    Route::get('/service/search', 'FilterController@serviceSearch')->name('service.search');
    Route::get('/search/search', 'FilterController@searchAll')->name('search.search');
    Route::get('/service/filter', 'FilterController@serviceDefault')->name('service.filter');
    Route::get('/service/category/{slug}/{id}', 'FilterController@serviceCategory')->name('service.category');
    Route::get('/service/sub/category/{slug}/{id}', 'FilterController@serviceSubCategory')->name('service.sub.category');

    //software
    Route::get('/product', 'SiteController@software')->name('software');
    Route::get('/product/details/{slug}/{id}', 'SiteController@softwareDetails')->name('software.details');
    Route::get('/product/search/filter', 'FilterController@softwareItemSearch')->name('software.search.filter');
    Route::get('/product/category/{slug}/{id}', 'FilterController@softwareCategory')->name('software.category');
    Route::get('/product/sub/category/{slug}/{id}', 'FilterController@softwareSubCategory')->name('software.sub.category');
    Route::get('/product/search/', 'FilterController@softwareSearch')->name('software.search');
    Route::get('/product/search/country', 'FilterController@softwareCountrySearch')->name('software.search.country');



    //job
    Route::get('/job', 'SiteController@job')->name('job');
    Route::get('/job/details/{slug}/{id}', 'SiteController@jobDeatils')->name('job.details');
    Route::get('/job/filter/search/', 'FilterController@jobItemSearch')->name('job.filter.search');
    Route::get('/job/category/{slug}/{id}', 'FilterController@jobCategory')->name('job.category');
    Route::get('/job/sub/category/{slug}/{id}', 'FilterController@jobSubCategory')->name('job.sub.category');
    Route::get('/job/search', 'FilterController@jobSearch')->name('job.search');


    Route::get('/user/{username}', 'SiteController@userProfile')->name('profile');
    Route::get('/user/service/{username}', 'SiteController@userService')->name('profile.service');
    Route::get('/user/product/{username}', 'SiteController@userSoftware')->name('profile.software');
    Route::get('/user/job/{username}', 'SiteController@userJob')->name('profile.job');
    Route::get('/add/{id}', 'SiteController@adclicked')->name('add.clicked');


    //Job Filter Search


    Route::post('/subscribe', 'SiteController@subscribe')->name('subscribe');
    Route::get('{slug}/{id}', 'SiteController@footerMenu')->name('footer.menu');
});

Route::get('get_wp_products', function () {
    Artisan::call('get_products');
    Artisan::call('get_products_sa');
    Artisan::call('get_products_uae');
    echo "Wordpress Products Imported Successfully!";
});
Route::get('get_wp_orders', function () {
    Artisan::call('get_orders');
    echo "Wordpress Orders Imported Successfully!";
});
Route::get('get_wp_price', function () {
    Artisan::call('get_shipping');
    echo "Wordpress Shipping Price Imported Successfully!";
});
Route::post('/chats/create', [\App\Http\Controllers\Admin\ConversationController::class,'Replychats'])->name('chats.create');

