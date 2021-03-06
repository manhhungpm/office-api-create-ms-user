<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'office:sync-domain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync domain for Office 365';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $accounts = Account::where('status', ACCOUNT_STATUS_ACTIVE)->get();

        foreach ($accounts as $account) {
            $data = sendRequest(API_DOMAIN, [], $account->access_token, 'GET');

            if ($data != '') {
                $result = json_decode($data);
                $domains = $result->value;

                foreach ($domains as $domain) {
                    Domain::updateOrCreate(
                        ['account_id' => $account->id, 'id' => $domain->id],
                        [
                            'authenticationType' => $domain->authenticationType,
                            'availabilityStatus' => $domain->availabilityStatus,
                            'isAdminManaged' => $domain->isAdminManaged,
                            'isDefault' => $domain->isDefault,
                            'isInitial' => $domain->isInitial,
                            'isRoot' => $domain->isRoot,
                            'isVerified' => $domain->isVerified,
                            'supportedServices' => json_encode($domain->supportedServices),
                            'state' => $domain->state,
                            'sync_at' => Carbon::now(),
                            'account_id' => $account->id
                        ]
                    );
                }

                //X??a nh???ng th???ng c?? sync_at nh??? h??n carbon->now - 15ph
                //t???i nh???ng th???ng c?? tr??n server ???????c ?????ng b??? th?? s??? ???????c ?????ng b??? l???i sync_at (1 ti???ng 1 l???n)
                //c??n n???u kh??ng c?? th?? sync at v???n gi??? nguy??n

                $domainDelete = Domain::select('domain_id')
                    ->where('account_id',$account->id)
                    ->where('sync_at', '<', Carbon::now()->subMinutes(15))
                    ->get()->toArray()
                ;
                $arrId = [];
                foreach ($domainDelete as $item) {
                    array_push($arrId, $item['domain_id']);
                }
                Domain::destroy($arrId);
            }
        }
    }
}
