import PrivateDocumentController from './PrivateDocumentController'
import ProvinceController from './ProvinceController'
import VoucherController from './VoucherController'
import PartnerApplicationController from './PartnerApplicationController'
const Public = {
    PrivateDocumentController: Object.assign(PrivateDocumentController, PrivateDocumentController),
ProvinceController: Object.assign(ProvinceController, ProvinceController),
VoucherController: Object.assign(VoucherController, VoucherController),
PartnerApplicationController: Object.assign(PartnerApplicationController, PartnerApplicationController),
}

export default Public