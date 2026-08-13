import PrivateDocumentController from './PrivateDocumentController'
import ProvinceController from './ProvinceController'
import PartnerApplicationController from './PartnerApplicationController'

const Public = {
    PrivateDocumentController: Object.assign(PrivateDocumentController, PrivateDocumentController),
    ProvinceController: Object.assign(ProvinceController, ProvinceController),
    PartnerApplicationController: Object.assign(PartnerApplicationController, PartnerApplicationController),
}

export default Public