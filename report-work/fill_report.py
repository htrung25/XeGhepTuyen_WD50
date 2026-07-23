from copy import deepcopy
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
import re
import shutil
import tempfile

from lxml import etree

NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
W = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"

SOURCE = Path("report-work/template-convert/Du an TN Project Report Template.docx")
OUTPUT = Path("report-work/Bao-cao-Xe-Ghep-Tuyen-F-Group-den-muc-3.6.docx")


def para_text(p):
    return "".join(p.xpath(".//w:t/text()", namespaces=NS)).strip()


def set_text(p, text, *, bold=None, color="000000", size=26, justify=True):
    for child in list(p):
        if child.tag != W + "pPr":
            p.remove(child)
    ppr = p.find("w:pPr", NS)
    if ppr is None:
        ppr = etree.Element(W + "pPr")
        p.insert(0, ppr)
    if justify:
        jc = ppr.find("w:jc", NS)
        if jc is None:
            jc = etree.SubElement(ppr, W + "jc")
        jc.set(W + "val", "both")
    spacing = ppr.find("w:spacing", NS)
    if spacing is None:
        spacing = etree.SubElement(ppr, W + "spacing")
    spacing.set(W + "after", "120")
    spacing.set(W + "line", "360")
    spacing.set(W + "lineRule", "auto")
    r = etree.SubElement(p, W + "r")
    rpr = etree.SubElement(r, W + "rPr")
    fonts = etree.SubElement(rpr, W + "rFonts")
    for key in ("ascii", "hAnsi", "eastAsia", "cs"):
        fonts.set(W + key, "Times New Roman")
    if bold is True:
        etree.SubElement(rpr, W + "b")
        etree.SubElement(rpr, W + "bCs")
    col = etree.SubElement(rpr, W + "color")
    col.set(W + "val", color)
    sz = etree.SubElement(rpr, W + "sz")
    sz.set(W + "val", str(size))
    szcs = etree.SubElement(rpr, W + "szCs")
    szcs.set(W + "val", str(size))
    t = etree.SubElement(r, W + "t")
    if text.startswith(" ") or text.endswith(" "):
        t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
    t.text = text
    return p


def clone_as(template, text, **kwargs):
    p = deepcopy(template)
    return set_text(p, text, **kwargs)


part1 = [
    ("h2", "1.1 Giới thiệu đề tài"),
    ("h3", "1.1.1 Lý do chọn đề tài"),
    ("body", "Trong những năm gần đây, nhu cầu di chuyển liên tỉnh phục vụ học tập, làm việc, du lịch và thăm thân ngày càng đa dạng. Tuy nhiên, người có nhu cầu đi xe ghép thường phải tìm chuyến qua nhiều kênh rời rạc như mạng xã hội, điện thoại hoặc nhóm trò chuyện. Thông tin về giờ chạy, điểm đón trả, số ghế còn lại và giá vé khó được kiểm chứng; việc thay đổi hoặc hủy chuyến cũng thiếu một quy trình thống nhất."),
    ("body", "Ở phía nhà xe và tài xế, dữ liệu khách hàng, lịch chạy, phương tiện và doanh thu dễ bị phân tán khi quản lý thủ công. Một ghế trống trên chuyến xe là nguồn lực bị lãng phí, trong khi hành khách vẫn gặp khó khăn khi tìm phương án di chuyển phù hợp. Bài toán vì vậy không chỉ là đặt xe trực tuyến mà còn là đồng bộ toàn bộ vòng đời chuyến đi giữa hành khách, nhà xe, tài xế và bộ phận quản trị."),
    ("body", "Đề tài “Xe Ghép Tuyến F-Group” được lựa chọn nhằm xây dựng một hệ thống web tập trung, cho phép hành khách tìm chuyến và chọn ghế; nhà xe quản lý tuyến, lịch chạy, phương tiện và tài xế; tài xế thực hiện chuyến và cập nhật vị trí; quản trị viên kiểm soát tài khoản, tài chính, phân quyền và nhật ký hoạt động. Giải pháp hướng đến tính minh bạch, thuận tiện và an toàn trong vận hành."),
    ("h3", "1.1.2 Mục tiêu của đề tài"),
    ("body", "Mục tiêu tổng quát của đề tài là xây dựng một nền tảng web có khả năng hỗ trợ nghiệp vụ đặt xe ghép liên tỉnh từ khâu tìm kiếm chuyến đến khi hoàn tất và đánh giá chuyến đi."),
    ("body", "Các mục tiêu cụ thể gồm: (1) cung cấp chức năng đăng ký, đăng nhập và quản lý hồ sơ theo từng nhóm người dùng; (2) cho phép tìm chuyến theo điểm đi, điểm đến và ngày khởi hành; (3) hiển thị lịch chạy, sơ đồ ghế, điểm đón trả và giá vé rõ ràng; (4) khóa ghế tạm thời để hạn chế trùng chỗ khi nhiều người đặt cùng lúc; (5) hỗ trợ tạo vé, thanh toán, áp dụng mã giảm giá, hủy vé và hoàn tiền theo trạng thái; (6) tạo mã QR phục vụ đối soát khi đón khách."),
    ("body", "Hệ thống đồng thời đặt mục tiêu hỗ trợ nhà xe quản lý tuyến, chuyến, điểm dừng, phương tiện, tài xế và báo cáo doanh thu; hỗ trợ tài xế xem lịch được phân công, bắt đầu/kết thúc chuyến và cập nhật vị trí; cung cấp thông báo, theo dõi hành trình, đánh giá và yêu cầu hỗ trợ cho khách hàng; cung cấp cho quản trị viên công cụ quản lý người dùng, đối tác, vai trò, tài chính, hoàn tiền và nhật ký kiểm toán."),
    ("body", "Về mặt kỹ thuật, sản phẩm được tổ chức theo kiến trúc tách frontend và backend, giao tiếp qua REST API; áp dụng xác thực Bearer token, phân quyền theo vai trò, kiểm tra dữ liệu đầu vào, xử lý hàng đợi cho tác vụ nền, kiểm thử tự động và cơ chế thời gian thực khi cần thiết. Sản phẩm cần có giao diện thích ứng trên máy tính và thiết bị di động, dữ liệu nhất quán và quy trình triển khai rõ ràng."),
    ("h2", "1.2 Thành viên tham gia dự án"),
    ("placeholder", "<<Sinh viên bổ sung danh sách thành viên và vai trò theo phiếu đăng ký đề tài>>"),
    ("h2", "1.3 Các công cụ và công nghệ sử dụng"),
    ("h3", "1.3.1 Các công cụ"),
    ("body", "Visual Studio Code được sử dụng để phát triển và quản lý mã nguồn. Git và GitHub hỗ trợ quản lý phiên bản, theo dõi thay đổi và phối hợp phát triển. Composer quản lý thư viện PHP; npm quản lý thư viện JavaScript. Postman hoặc tài liệu OpenAPI/Swagger hỗ trợ kiểm tra và mô tả API. Figma có thể được sử dụng để thiết kế, đối chiếu giao diện; MySQL Workbench hoặc công cụ tương đương hỗ trợ theo dõi cơ sở dữ liệu."),
    ("body", "Pest/PHPUnit được dùng cho kiểm thử backend; Vitest kiểm thử đơn vị và hợp đồng ở frontend; Playwright kiểm thử luồng sử dụng trên trình duyệt. Laravel Pint, ESLint, Prettier và vue-tsc hỗ trợ chuẩn hóa mã nguồn, kiểm tra lỗi cú pháp, định dạng và kiểu dữ liệu."),
    ("h3", "1.3.2 Các công nghệ"),
    ("body", "Frontend sử dụng Vue 3, TypeScript, Vite, Vue Router và Pinia. Giao diện được xây dựng với Tailwind CSS; Axios đảm nhiệm giao tiếp HTTP; Mapbox GL hiển thị bản đồ và vị trí; Laravel Echo, Reverb và Pusher JS phục vụ cập nhật thời gian thực. Hệ thống frontend được tổ chức thành bốn cổng: khách hàng, tài xế, nhà xe và quản trị viên."),
    ("body", "Backend sử dụng PHP 8.3 và Laravel 13 để xây dựng REST API. Laravel Sanctum thực hiện xác thực token; MySQL 8 lưu trữ dữ liệu quan hệ và dữ liệu không gian; Redis hỗ trợ cache, hàng đợi và phiên; Laravel Queue/Scheduler xử lý tác vụ nền và công việc định kỳ. Hệ thống tích hợp mã QR, kênh thông báo email/SMS/Zalo, cổng thanh toán và webhook theo cấu hình. Phương án triển khai dự kiến sử dụng Vercel cho frontend và Laravel Cloud cho backend."),
]

part2 = [
    ("h2", "2.1 Bài toán nghiệp vụ"),
    ("body", "Xe ghép liên tỉnh là mô hình trong đó nhiều hành khách có nhu cầu di chuyển cùng hướng sử dụng chung một chuyến xe. Nghiệp vụ cốt lõi gồm công bố nguồn cung chuyến đi, tiếp nhận nhu cầu, phân bổ ghế, thu tiền, tổ chức đón trả, theo dõi thực hiện chuyến và đối soát doanh thu. Do có nhiều bên tham gia, mọi thay đổi về lịch, ghế, tài xế, điểm đón trả hoặc trạng thái thanh toán cần được cập nhật đồng bộ."),
    ("body", "Quy trình bắt đầu khi nhà xe thiết lập tuyến đường, các điểm dừng, phương tiện và sơ đồ ghế; sau đó tạo lịch chạy và phân công tài xế. Hành khách tìm chuyến theo địa điểm và ngày đi, xem thông tin chi tiết, chọn điểm đón trả và ghế còn trống. Khi hành khách xác nhận đặt chỗ, hệ thống khóa ghế trong thời gian giới hạn để ngăn hai người cùng mua một ghế, tính giá, áp dụng voucher nếu hợp lệ và tạo đơn đặt vé."),
    ("body", "Hành khách lựa chọn phương thức thanh toán được hệ thống hỗ trợ. Kết quả thanh toán được xác nhận qua API hoặc webhook; khi thành công, vé và mã QR được phát hành, đồng thời các bên nhận thông báo. Nếu người dùng không thanh toán đúng hạn, tác vụ nền tự động hết hạn đơn và giải phóng ghế. Khi hủy vé, hệ thống kiểm tra điều kiện, cập nhật trạng thái và thực hiện hoàn tiền về nguồn phù hợp hoặc ví điện tử nội bộ theo quy trình."),
    ("body", "Trước và trong chuyến đi, tài xế xem lịch được phân công, kiểm tra danh sách hành khách, bắt đầu chuyến và cập nhật vị trí. Hành khách có thể theo dõi trạng thái chuyến bằng tài khoản hoặc mã theo dõi. Khi chuyến hoàn thành, hệ thống chốt trạng thái, cho phép hành khách đánh giá và ghi nhận dữ liệu phục vụ báo cáo doanh thu, thanh toán cho đối tác và kiểm toán."),
    ("body", "Các ràng buộc quan trọng gồm: chỉ bán ghế thực sự còn trống; không cho đặt chuyến đã đóng hoặc không còn khả dụng; bảo vệ thông tin cá nhân và giao dịch; phân quyền đúng theo vai trò; lưu vết thao tác quản trị; xử lý webhook và tác vụ nền theo nguyên tắc idempotent để tránh ghi nhận thanh toán hoặc hoàn tiền nhiều lần; bảo đảm dữ liệu điểm đón trả nằm trong vùng phục vụ đã cấu hình."),
    ("h2", "2.2 Hệ thống tương tự"),
    ("body", "Vexere là nền tảng đặt vé xe khách trực tuyến kết nối hành khách với nhiều nhà xe và tuyến đường. Người dùng có thể tìm chuyến, đặt vé và nhận ưu đãi; phía nhà xe có công cụ quản lý vé, lịch chạy, ghế, tài xế, phương tiện và doanh thu. Đây là hệ thống tham khảo phù hợp cho nghiệp vụ bán vé tập trung, đồng bộ ghế và vận hành nhà xe."),
    ("body", "Đi Chung cung cấp dịch vụ đi riêng và đi ghép. Với hình thức đi ghép, hệ thống tìm hành khách cùng tuyến để ghép chuyến, qua đó tối ưu chi phí và chỗ ngồi. Dịch vụ chú trọng điểm đón trả theo yêu cầu, khoảng thời gian đón dự kiến và quy định hành lý. Đây là nguồn tham khảo trực tiếp cho mô hình ghép nhu cầu di chuyển và tổ chức đón trả."),
    ("body", "Dịch vụ be Đường dài cho phép khách hàng đặt xe đi từ thành phố đến khu vực tỉnh, thành lân cận, hỗ trợ chuyến một chiều hoặc hai chiều và hiển thị chi phí trên ứng dụng. Hệ thống be đồng thời có quy trình dành cho đối tác tài xế. Đây là nguồn tham khảo về trải nghiệm đặt xe đường dài, minh bạch giá và phối hợp giữa khách hàng với tài xế."),
    ("body", "So với các hệ thống tham khảo, Xe Ghép Tuyến F-Group tập trung vào một sản phẩm web phục vụ đồng thời bốn nhóm người dùng và bao quát cả đặt ghế theo chuyến lẫn vận hành nhà xe. Phạm vi đề tài nhấn mạnh quản lý tuyến/chuyến/ghế, phân công tài xế, theo dõi hành trình, thanh toán–hoàn tiền, ví, hỗ trợ khách hàng, báo cáo tài chính, phân quyền quản trị và nhật ký kiểm toán trong một hệ thống thống nhất."),
    ("source", "Nguồn khảo sát: Vexere — vexere.com, hotro.vexere.com."),
    ("source", "Đi Chung — dichung.vn/docs/faq-dich-vu-van-chuyen-hanh-khach/."),
    ("source", "be Đường dài — beacademy.be.com.vn."),
    ("h2", "2.3 Đối tượng sử dụng hệ thống"),
    ("body", "Khách vãng lai: truy cập trang công khai, tìm kiếm và xem chi tiết chuyến, kiểm tra ghế, theo dõi chuyến bằng mã và gửi đăng ký trở thành đối tác nhà xe. Khi cần đặt vé hoặc quản lý giao dịch, người dùng phải đăng ký/đăng nhập tài khoản khách hàng."),
    ("body", "Khách hàng: quản lý hồ sơ; tìm chuyến; chọn điểm đón trả và ghế; đặt vé, thanh toán, sử dụng voucher; xem vé và mã QR; theo dõi chuyến; hủy vé theo điều kiện; quản lý ví và lịch sử giao dịch; nhận thông báo; đánh giá chuyến đi và gửi yêu cầu hỗ trợ."),
    ("body", "Tài xế: quản lý thông tin cá nhân và nghiệp vụ; xem các chuyến được phân công; theo dõi danh sách hành khách; bắt đầu, cập nhật vị trí và hoàn thành chuyến; báo cáo sự cố hoặc tình trạng không thể thực hiện chuyến; phối hợp với nhà xe trong quá trình vận hành."),
    ("body", "Nhà xe/nhân viên điều hành: quản lý hồ sơ đơn vị, tuyến đường, điểm dừng, vùng phục vụ, phương tiện, sơ đồ ghế, tài xế và lịch chạy; phân công tài xế; theo dõi đặt vé, doanh thu và tình trạng vận hành; tiếp nhận thông tin liên quan đến chuyến và hành khách."),
    ("body", "Quản trị viên: quản lý tài khoản, đối tác, nhân sự quản trị, vai trò và quyền hạn; giám sát dashboard, tài chính, doanh thu, hoàn tiền và thanh toán đối tác; quản lý cấu hình vùng phục vụ; xử lý thông báo, yêu cầu hỗ trợ và nhật ký kiểm toán. Quyền thao tác được giới hạn theo vai trò quản trị được cấp."),
    ("body", "Hệ thống bên ngoài: cổng thanh toán gửi kết quả giao dịch qua callback/webhook; dịch vụ bản đồ cung cấp bản đồ và dữ liệu vị trí; hệ thống email, SMS, Zalo và WebSocket chuyển thông báo; hạ tầng MySQL, Redis, lưu trữ và hàng đợi hỗ trợ vận hành kỹ thuật."),
]

part3_1 = [
    ("swot_heading", "3.1.1 Điểm mạnh (Strengths)"),
    ("body", "Xe Ghép Tuyến F-Group có phạm vi nghiệp vụ tương đối đầy đủ và được tổ chức thống nhất trên một nền tảng. Hệ thống phục vụ đồng thời khách hàng, tài xế, nhà xe và quản trị viên; dữ liệu về tuyến đường, lịch chạy, phương tiện, sơ đồ ghế, tài xế, đặt vé và thanh toán được liên kết với nhau. Cách tổ chức này giúp giảm tình trạng thông tin phân tán và tạo điều kiện theo dõi toàn bộ vòng đời chuyến đi."),
    ("body", "Quy trình đặt vé được thiết kế sát với nghiệp vụ thực tế: tìm chuyến theo địa điểm và ngày đi, chọn điểm đón trả, xem ghế còn trống, khóa ghế tạm thời, tạo đơn, thanh toán, phát hành mã QR, theo dõi chuyến, hủy vé và hoàn tiền. Cơ chế khóa ghế và xử lý đơn quá hạn góp phần hạn chế trùng chỗ khi nhiều khách hàng thao tác đồng thời."),
    ("body", "Về kỹ thuật, hệ thống tách frontend Vue 3/TypeScript và backend Laravel REST API, thuận lợi cho bảo trì và mở rộng. Xác thực Bearer token, phân quyền theo vai trò, nhật ký kiểm toán, hàng đợi, scheduler và xử lý idempotent cho thanh toán/hoàn tiền giúp tăng độ an toàn và nhất quán dữ liệu. Redis, WebSocket và dữ liệu không gian hỗ trợ cache, cập nhật thời gian thực, theo dõi vị trí và kiểm tra vùng phục vụ."),
    ("swot_heading", "3.1.2 Điểm yếu (Weaknesses)"),
    ("body", "Phạm vi chức năng lớn làm tăng độ phức tạp của sản phẩm. Bốn cổng người dùng, nhiều trạng thái chuyến–vé–thanh toán và các quy trình hoàn tiền, đối soát, thông báo đòi hỏi lượng kiểm thử và dữ liệu thử nghiệm đáng kể. Khi nguồn lực phát triển còn hạn chế, việc hoàn thiện đồng đều trải nghiệm ở mọi cổng là một thách thức."),
    ("body", "Chất lượng dịch vụ phụ thuộc mạnh vào dữ liệu và hoạt động của nhà xe, tài xế. Nếu lịch chạy, vùng phục vụ, sơ đồ ghế hoặc vị trí xe không được cập nhật kịp thời thì kết quả tìm kiếm và theo dõi có thể thiếu chính xác. Hệ thống cũng chưa thể tự tạo ra mạng lưới cung ứng; trong giai đoạn đầu, số tuyến, nhà xe và tài xế ít sẽ làm giảm lựa chọn của hành khách."),
    ("body", "Sản phẩm phụ thuộc vào nhiều dịch vụ bên ngoài như bản đồ, cổng thanh toán, email, SMS, Zalo và WebSocket. Lỗi kết nối, thay đổi chính sách, giới hạn truy cập hoặc chi phí dịch vụ có thể ảnh hưởng trực tiếp đến vận hành. Việc triển khai tách frontend và backend còn yêu cầu cấu hình chính xác CORS, biến môi trường, queue worker, scheduler và kênh thời gian thực."),
    ("swot_heading", "3.1.3 Cơ hội (Opportunities)"),
    ("body", "Nhu cầu di chuyển liên tỉnh và thói quen tìm kiếm, thanh toán dịch vụ trực tuyến tạo điều kiện cho một nền tảng đặt xe ghép phát triển. Việc số hóa giúp hành khách chủ động so sánh chuyến, giá, điểm đón trả và tình trạng ghế; đồng thời giúp nhà xe tiếp cận khách hàng ngoài các kênh bán vé truyền thống."),
    ("body", "Mô hình xe ghép có khả năng tận dụng ghế trống, tăng tỷ lệ lấp đầy và tối ưu doanh thu trên mỗi chuyến. Dữ liệu tích lũy về nhu cầu, tuyến phổ biến, khung giờ, tỷ lệ hủy và đánh giá có thể được sử dụng để điều chỉnh lịch chạy, phân công phương tiện, thiết kế chương trình ưu đãi và nâng cao chất lượng phục vụ."),
    ("body", "Kiến trúc hiện tại tạo nền tảng để mở rộng sang ứng dụng di động, gợi ý chuyến phù hợp, dự báo nhu cầu, tối ưu lộ trình, chương trình khách hàng thân thiết, kết nối thêm nhà xe và nhiều phương thức thanh toán. Hệ thống cũng có thể phát triển API tích hợp với đối tác du lịch, lưu trú hoặc các nền tảng bán vé khác."),
    ("swot_heading", "3.1.4 Thách thức (Threats)"),
    ("body", "Thị trường đã có các nền tảng đặt vé, gọi xe và vận chuyển đường dài có thương hiệu, lượng người dùng và mạng lưới đối tác lớn. Xe Ghép Tuyến F-Group phải tạo được khác biệt rõ ràng về độ thuận tiện, độ tin cậy, phạm vi tuyến và chất lượng hỗ trợ mới có khả năng thu hút cả hành khách lẫn nhà xe."),
    ("body", "Hoạt động vận tải liên quan trực tiếp đến an toàn hành khách, giấy tờ của tài xế–phương tiện, bảo vệ dữ liệu cá nhân, thanh toán và xử lý khiếu nại. Sự cố chuyến đi, gian lận, tài khoản giả, thanh toán sai, lộ dữ liệu hoặc tranh chấp hoàn tiền có thể ảnh hưởng lớn đến uy tín của hệ thống."),
    ("body", "Nhu cầu đi lại có thể biến động theo mùa, ngày lễ, thời tiết và tình hình giao thông. Giá nhiên liệu, chi phí vận hành, thay đổi chính sách của đối tác hoặc gián đoạn hạ tầng cũng tác động đến giá và khả năng cung ứng chuyến. Hệ thống cần có cơ chế giám sát, sao lưu, phục hồi, xử lý sự cố và truyền thông kịp thời."),
    ("swot_heading", "3.1.5 Định hướng từ kết quả SWOT"),
    ("body", "Từ phân tích trên, dự án nên ưu tiên hoàn thiện luồng nghiệp vụ cốt lõi và độ tin cậy trước khi mở rộng quy mô: chuẩn hóa dữ liệu tuyến–chuyến–ghế, tăng kiểm thử cho khóa ghế và thanh toán, giám sát queue/webhook, xác minh đối tác, bảo vệ dữ liệu và xây dựng quy trình hỗ trợ–hoàn tiền minh bạch. Trong giai đoạn triển khai thử nghiệm, có thể tập trung vào một số tuyến có nhu cầu rõ ràng để bảo đảm đủ nguồn cung, thu thập phản hồi và điều chỉnh sản phẩm."),
]

part3_3 = [
    ("swot_heading", "3.3.1 Use case của khách vãng lai"),
    ("case", "UC-GU-01 – Tìm kiếm chuyến đi."),
    ("case", "UC-GU-02 – Xem chi tiết chuyến đi."),
    ("case", "UC-GU-03 – Xem tình trạng ghế."),
    ("case", "UC-GU-04 – Theo dõi chuyến đi bằng mã theo dõi."),
    ("case", "UC-GU-05 – Gửi đăng ký trở thành đối tác nhà xe."),
    ("swot_heading", "3.3.2 Use case của khách hàng"),
    ("case", "UC-CU-01 – Đăng ký tài khoản."),
    ("case", "UC-CU-02 – Đăng nhập tài khoản."),
    ("case", "UC-CU-03 – Xác thực mã OTP."),
    ("case", "UC-CU-04 – Cập nhật hồ sơ cá nhân."),
    ("case", "UC-CU-05 – Đổi mật khẩu."),
    ("case", "UC-CU-06 – Đăng xuất tài khoản."),
    ("case", "UC-CU-07 – Tìm kiếm chuyến đi."),
    ("case", "UC-CU-08 – Xem chi tiết chuyến đi."),
    ("case", "UC-CU-09 – Chọn điểm đón và điểm trả."),
    ("case", "UC-CU-10 – Chọn và khóa ghế tạm thời."),
    ("case", "UC-CU-11 – Đặt vé xe."),
    ("case", "UC-CU-12 – Áp dụng mã giảm giá."),
    ("case", "UC-CU-13 – Khởi tạo thanh toán."),
    ("case", "UC-CU-14 – Kiểm tra trạng thái thanh toán."),
    ("case", "UC-CU-15 – Xem danh sách vé đã đặt."),
    ("case", "UC-CU-16 – Xem chi tiết vé."),
    ("case", "UC-CU-17 – Xem mã QR của vé."),
    ("case", "UC-CU-18 – Theo dõi hành trình chuyến đi."),
    ("case", "UC-CU-19 – Hủy vé."),
    ("case", "UC-CU-20 – Xem số dư ví."),
    ("case", "UC-CU-21 – Nạp tiền vào ví."),
    ("case", "UC-CU-22 – Xem lịch sử giao dịch ví."),
    ("case", "UC-CU-23 – Đánh giá chuyến đi."),
    ("case", "UC-CU-24 – Xem và đánh dấu thông báo."),
    ("case", "UC-CU-25 – Gửi và trao đổi yêu cầu hỗ trợ."),
    ("swot_heading", "3.3.3 Use case của tài xế"),
    ("case", "UC-DR-01 – Đăng nhập cổng tài xế."),
    ("case", "UC-DR-02 – Xem và cập nhật hồ sơ tài xế."),
    ("case", "UC-DR-03 – Xem danh sách chuyến được phân công."),
    ("case", "UC-DR-04 – Xem chi tiết chuyến được phân công."),
    ("case", "UC-DR-05 – Xem danh sách hành khách."),
    ("case", "UC-DR-06 – Bắt đầu chuyến đi."),
    ("case", "UC-DR-07 – Cập nhật vị trí xe."),
    ("case", "UC-DR-08 – Hoàn thành chuyến đi."),
    ("case", "UC-DR-09 – Báo cáo không thể thực hiện chuyến."),
    ("case", "UC-DR-10 – Xem và đánh dấu thông báo."),
    ("swot_heading", "3.3.4 Use case của nhà xe/nhân viên điều hành"),
    ("case", "UC-OP-01 – Đăng nhập cổng nhà xe."),
    ("case", "UC-OP-02 – Xem và cập nhật hồ sơ nhà xe."),
    ("case", "UC-OP-03 – Quản lý tuyến đường."),
    ("case", "UC-OP-04 – Quản lý điểm dừng và điểm đón trả."),
    ("case", "UC-OP-05 – Quản lý vùng phục vụ."),
    ("case", "UC-OP-06 – Quản lý phương tiện."),
    ("case", "UC-OP-07 – Quản lý sơ đồ ghế."),
    ("case", "UC-OP-08 – Quản lý tài xế."),
    ("case", "UC-OP-09 – Quản lý lịch chạy."),
    ("case", "UC-OP-10 – Phân công tài xế cho chuyến."),
    ("case", "UC-OP-11 – Theo dõi danh sách đặt vé."),
    ("case", "UC-OP-12 – Theo dõi trạng thái chuyến."),
    ("case", "UC-OP-13 – Xem báo cáo doanh thu."),
    ("case", "UC-OP-14 – Xem và đánh dấu thông báo."),
    ("swot_heading", "3.3.5 Use case của quản trị viên"),
    ("case", "UC-AD-01 – Đăng nhập cổng quản trị."),
    ("case", "UC-AD-02 – Xem dashboard tổng quan."),
    ("case", "UC-AD-03 – Quản lý hồ sơ quản trị viên."),
    ("case", "UC-AD-04 – Quản lý người dùng."),
    ("case", "UC-AD-05 – Quản lý đối tác nhà xe."),
    ("case", "UC-AD-06 – Duyệt hoặc từ chối tài xế."),
    ("case", "UC-AD-07 – Quản lý nhân sự quản trị."),
    ("case", "UC-AD-08 – Quản lý vai trò và quyền hạn."),
    ("case", "UC-AD-09 – Quản lý cấu hình vùng phục vụ."),
    ("case", "UC-AD-10 – Theo dõi doanh thu và giao dịch."),
    ("case", "UC-AD-11 – Xử lý hoàn tiền."),
    ("case", "UC-AD-12 – Quản lý thanh toán cho đối tác."),
    ("case", "UC-AD-13 – Xem báo cáo tài chính."),
    ("case", "UC-AD-14 – Quản lý thông báo."),
    ("case", "UC-AD-15 – Quản lý yêu cầu hỗ trợ."),
    ("case", "UC-AD-16 – Xem nhật ký kiểm toán."),
    ("swot_heading", "3.3.6 Use case của hệ thống bên ngoài"),
    ("case", "UC-EX-01 – Xác nhận kết quả thanh toán qua callback/webhook."),
    ("case", "UC-EX-02 – Cung cấp bản đồ, tọa độ và dữ liệu vị trí."),
    ("case", "UC-EX-03 – Gửi thông báo qua email, SMS hoặc Zalo."),
    ("case", "UC-EX-04 – Phát sự kiện vị trí và trạng thái theo thời gian thực."),
    ("case", "UC-EX-05 – Tự động hết hạn đơn chưa thanh toán và giải phóng ghế."),
    ("case", "UC-EX-06 – Tự động xử lý hoàn tiền và cập nhật trạng thái chuyến."),
]

usecase_descriptions = [
    ("UC-CU-01", "Đăng ký tài khoản", "Khách hàng tạo tài khoản mới để sử dụng các chức năng đặt vé và quản lý giao dịch.", "Họ tên, số điện thoại, email, mật khẩu và thông tin xác thực theo yêu cầu.", "Tài khoản khách hàng được tạo hoặc thông báo dữ liệu không hợp lệ/trùng lặp."),
    ("UC-CU-02", "Đăng nhập tài khoản", "Người dùng xác thực để truy cập đúng cổng và các chức năng theo vai trò.", "Số điện thoại/email và mật khẩu.", "Bearer token, thông tin người dùng và kết quả đăng nhập."),
    ("UC-CU-03", "Xác thực mã OTP", "Khách hàng xác nhận số điện thoại hoặc yêu cầu xác thực bằng mã dùng một lần.", "Số điện thoại và mã OTP.", "Trạng thái xác thực thành công hoặc thông báo mã sai/hết hạn."),
    ("UC-CU-07", "Tìm kiếm chuyến đi", "Khách hàng tìm các chuyến phù hợp theo nhu cầu di chuyển.", "Điểm đi, điểm đến, ngày khởi hành và số hành khách.", "Danh sách chuyến còn khả dụng kèm giờ chạy, giá và số ghế trống."),
    ("UC-CU-08", "Xem chi tiết chuyến đi", "Khách hàng xem đầy đủ thông tin trước khi quyết định đặt vé.", "Mã chuyến đi.", "Thông tin tuyến, nhà xe, phương tiện, điểm đón trả, thời gian, giá và chính sách."),
    ("UC-CU-10", "Chọn và khóa ghế tạm thời", "Khách hàng chọn ghế; hệ thống giữ ghế trong thời gian giới hạn để tránh đặt trùng.", "Mã chuyến, danh sách ghế và người đặt.", "Mã khóa ghế, thời hạn giữ ghế hoặc thông báo ghế không còn khả dụng."),
    ("UC-CU-11", "Đặt vé xe", "Khách hàng tạo đơn đặt vé từ chuyến, ghế và thông tin hành khách đã chọn.", "Mã khóa ghế, điểm đón trả, thông tin hành khách và phương thức thanh toán.", "Đơn đặt vé ở trạng thái phù hợp, mã đặt chỗ và số tiền cần thanh toán."),
    ("UC-CU-12", "Áp dụng mã giảm giá", "Khách hàng sử dụng voucher hợp lệ để giảm giá trị đơn đặt vé.", "Mã voucher, thông tin chuyến và giá trị đơn.", "Số tiền được giảm, tổng tiền mới hoặc lý do voucher không hợp lệ."),
    ("UC-CU-13", "Khởi tạo thanh toán", "Khách hàng lựa chọn kênh thanh toán và tạo giao dịch cho đơn đặt vé.", "Mã đơn đặt vé, phương thức và số tiền thanh toán.", "Mã giao dịch, URL/QR thanh toán hoặc hướng dẫn thanh toán tương ứng."),
    ("UC-EX-01", "Xác nhận kết quả thanh toán", "Cổng thanh toán gửi callback/webhook; hệ thống xác minh và cập nhật giao dịch theo nguyên tắc idempotent.", "Mã giao dịch, trạng thái, số tiền, chữ ký và dữ liệu từ cổng thanh toán.", "Trạng thái thanh toán/vé được cập nhật và phản hồi xác nhận cho cổng thanh toán."),
    ("UC-CU-17", "Xem mã QR của vé", "Khách hàng lấy mã QR dùng để đối soát khi lên xe.", "Mã vé hoặc mã đơn đặt vé đã thanh toán.", "Mã QR hợp lệ gắn với vé và thông tin chuyến."),
    ("UC-CU-18", "Theo dõi hành trình chuyến đi", "Khách hàng theo dõi trạng thái và vị trí của chuyến đang thực hiện.", "Mã vé, tài khoản hoặc mã theo dõi.", "Dòng thời gian trạng thái, vị trí gần nhất và thông tin chuyến."),
    ("UC-CU-19", "Hủy vé", "Khách hàng yêu cầu hủy vé; hệ thống kiểm tra điều kiện và tính khoản hoàn.", "Mã vé, lý do hủy và thông tin xác thực.", "Vé được hủy hoặc bị từ chối; khoản hoàn và trạng thái hoàn tiền nếu có."),
    ("UC-CU-21", "Nạp tiền vào ví", "Khách hàng tạo giao dịch nạp tiền vào ví nội bộ.", "Số tiền và phương thức thanh toán.", "Giao dịch nạp tiền; số dư được cập nhật sau khi thanh toán thành công."),
    ("UC-CU-23", "Đánh giá chuyến đi", "Khách hàng đánh giá chất lượng sau khi chuyến hoàn thành.", "Mã vé, số sao và nội dung nhận xét.", "Đánh giá được ghi nhận hoặc thông báo không đủ điều kiện đánh giá."),
    ("UC-CU-25", "Gửi yêu cầu hỗ trợ", "Khách hàng tạo phiếu hỗ trợ và trao đổi với bộ phận xử lý.", "Danh mục, tiêu đề, nội dung, mức ưu tiên và tệp đính kèm nếu có.", "Mã phiếu hỗ trợ, trạng thái xử lý và lịch sử trao đổi."),
    ("UC-DR-03", "Xem chuyến được phân công", "Tài xế xem các chuyến mà nhà xe đã giao thực hiện.", "Tài khoản tài xế và bộ lọc thời gian/trạng thái.", "Danh sách chuyến cùng lịch, phương tiện và điểm đón trả."),
    ("UC-DR-05", "Xem danh sách hành khách", "Tài xế kiểm tra hành khách và điểm đón trả của chuyến.", "Mã chuyến được phân công.", "Danh sách hành khách, ghế, số liên hệ và trạng thái đón khách."),
    ("UC-DR-06", "Bắt đầu chuyến đi", "Tài xế xác nhận bắt đầu thực hiện chuyến sau khi đáp ứng điều kiện.", "Mã chuyến, tài xế và thông tin xác nhận.", "Trạng thái chuyến chuyển sang đang thực hiện và các bên nhận thông báo."),
    ("UC-DR-07", "Cập nhật vị trí xe", "Ứng dụng tài xế gửi tọa độ để phục vụ theo dõi thời gian thực.", "Mã chuyến, kinh độ, vĩ độ, thời gian và độ chính xác.", "Vị trí mới được lưu/phát sự kiện hoặc thông báo tọa độ không hợp lệ."),
    ("UC-DR-08", "Hoàn thành chuyến đi", "Tài xế xác nhận chuyến đã kết thúc để hệ thống chốt trạng thái.", "Mã chuyến và thông tin xác nhận hoàn thành.", "Chuyến và các vé liên quan được cập nhật; kích hoạt đối soát, đánh giá và thông báo."),
    ("UC-DR-09", "Báo cáo không thể thực hiện chuyến", "Tài xế thông báo sự cố khiến mình không thể tiếp tục chuyến được giao.", "Mã chuyến, lý do và ghi chú sự cố.", "Sự cố được ghi nhận; nhà xe nhận thông báo để phân công lại hoặc xử lý chuyến."),
    ("UC-OP-03", "Quản lý tuyến đường", "Nhà xe tạo, xem, cập nhật hoặc ngừng khai thác tuyến.", "Tên tuyến, điểm đầu/cuối, khoảng cách, thời gian dự kiến và trạng thái.", "Thông tin tuyến được lưu hoặc thông báo vi phạm dữ liệu/ràng buộc."),
    ("UC-OP-06", "Quản lý phương tiện", "Nhà xe quản lý xe dùng để phục vụ các chuyến.", "Biển số, loại xe, số ghế, sơ đồ ghế, giấy tờ và trạng thái.", "Hồ sơ phương tiện được tạo/cập nhật hoặc thông báo không hợp lệ."),
    ("UC-OP-08", "Quản lý tài xế", "Nhà xe quản lý hồ sơ và trạng thái tài xế thuộc đơn vị.", "Thông tin cá nhân, giấy phép lái xe, liên hệ và trạng thái.", "Hồ sơ tài xế được lưu, cập nhật hoặc chuyển trạng thái."),
    ("UC-OP-09", "Quản lý lịch chạy", "Nhà xe tạo và điều chỉnh chuyến theo tuyến, thời gian và phương tiện.", "Tuyến, ngày giờ, xe, giá, điểm dừng và trạng thái bán.", "Chuyến/lịch chạy được tạo hoặc thông báo xung đột nguồn lực."),
    ("UC-OP-10", "Phân công tài xế cho chuyến", "Nhà xe gán tài xế phù hợp cho một chuyến cụ thể.", "Mã chuyến và mã tài xế.", "Phân công được lưu, tài xế nhận thông báo hoặc hệ thống báo trùng lịch/không khả dụng."),
    ("UC-OP-13", "Xem báo cáo doanh thu", "Nhà xe theo dõi doanh thu và hiệu quả vận hành theo kỳ.", "Khoảng thời gian, tuyến, chuyến và bộ lọc trạng thái.", "Tổng doanh thu, số vé, tỷ lệ lấp đầy và dữ liệu chi tiết."),
    ("UC-AD-04", "Quản lý người dùng", "Quản trị viên tra cứu và kiểm soát tài khoản trên hệ thống.", "Từ khóa, vai trò, trạng thái và dữ liệu cập nhật.", "Danh sách/tài khoản được cập nhật, khóa hoặc mở khóa theo quyền."),
    ("UC-AD-05", "Quản lý đối tác nhà xe", "Quản trị viên tiếp nhận, xét duyệt và quản lý hồ sơ đối tác.", "Hồ sơ đăng ký, giấy tờ, kết quả thẩm định và ghi chú.", "Đối tác được duyệt, từ chối hoặc yêu cầu bổ sung thông tin."),
    ("UC-AD-08", "Quản lý vai trò và quyền hạn", "Quản trị viên cấu hình nhóm quyền cho nhân sự quản trị.", "Tên vai trò và tập quyền được cấp.", "Vai trò được tạo/cập nhật; quyền truy cập được áp dụng."),
    ("UC-AD-11", "Xử lý hoàn tiền", "Quản trị viên kiểm tra và thực hiện hoàn tiền cho giao dịch đủ điều kiện.", "Mã giao dịch/vé, số tiền, lý do và phương thức hoàn.", "Giao dịch hoàn tiền và số dư/trạng thái liên quan được cập nhật."),
    ("UC-AD-13", "Xem báo cáo tài chính", "Quản trị viên tổng hợp doanh thu, hoàn tiền, công nợ và khoản thanh toán đối tác.", "Kỳ báo cáo, nhà xe, loại giao dịch và trạng thái.", "Báo cáo tổng hợp, số liệu chi tiết và dữ liệu xuất báo cáo."),
    ("UC-AD-16", "Xem nhật ký kiểm toán", "Quản trị viên có quyền tra cứu các thao tác quan trọng trên hệ thống.", "Người thực hiện, hành động, đối tượng, thời gian và bộ lọc.", "Danh sách nhật ký gồm thời điểm, dữ liệu thay đổi và nguồn thao tác."),
]

permission_matrix = [
    ("Đăng ký tài khoản khách hàng", True, False, False, False, False),
    ("Đăng nhập hệ thống", False, True, True, True, True),
    ("Cập nhật hồ sơ cá nhân/đơn vị", False, True, True, True, True),
    ("Đổi mật khẩu và đăng xuất", False, True, True, True, True),
    ("Tìm kiếm và xem chuyến đi", True, True, False, False, True),
    ("Xem tình trạng ghế", True, True, False, True, True),
    ("Theo dõi chuyến bằng mã theo dõi", True, True, False, False, True),
    ("Gửi đăng ký đối tác nhà xe", True, False, False, False, True),
    ("Chọn điểm đón trả và khóa ghế", False, True, False, False, False),
    ("Đặt vé và xem vé đã đặt", False, True, False, True, True),
    ("Áp dụng mã giảm giá", False, True, False, False, True),
    ("Thanh toán và kiểm tra giao dịch", False, True, False, False, True),
    ("Xem mã QR của vé", False, True, True, True, True),
    ("Hủy vé và yêu cầu hoàn tiền", False, True, False, True, True),
    ("Quản lý ví và lịch sử giao dịch", False, True, False, False, True),
    ("Đánh giá chuyến đi", False, True, False, False, True),
    ("Gửi và xử lý yêu cầu hỗ trợ", False, True, False, True, True),
    ("Xem và đánh dấu thông báo", False, True, True, True, True),
    ("Xem chuyến được phân công", False, False, True, True, True),
    ("Xem danh sách hành khách", False, False, True, True, True),
    ("Bắt đầu và hoàn thành chuyến", False, False, True, True, True),
    ("Cập nhật vị trí xe", False, False, True, True, True),
    ("Báo cáo không thể thực hiện chuyến", False, False, True, True, True),
    ("Quản lý tuyến đường và điểm dừng", False, False, False, True, True),
    ("Quản lý vùng phục vụ", False, False, False, True, True),
    ("Quản lý phương tiện và sơ đồ ghế", False, False, False, True, True),
    ("Quản lý tài xế", False, False, False, True, True),
    ("Quản lý lịch chạy", False, False, False, True, True),
    ("Phân công tài xế cho chuyến", False, False, False, True, True),
    ("Theo dõi đặt vé và trạng thái chuyến", False, False, False, True, True),
    ("Xem báo cáo doanh thu nhà xe", False, False, False, True, True),
    ("Quản lý người dùng và đối tác", False, False, False, False, True),
    ("Duyệt hoặc từ chối tài xế", False, False, False, False, True),
    ("Quản lý nhân sự, vai trò và quyền hạn", False, False, False, False, True),
    ("Quản lý tài chính, hoàn tiền và thanh toán đối tác", False, False, False, False, True),
    ("Xem báo cáo tài chính", False, False, False, False, True),
    ("Quản lý thông báo toàn hệ thống", False, False, False, False, True),
    ("Xem nhật ký kiểm toán", False, False, False, False, True),
]


def make_matrix_table(headers, rows, widths):
    tbl = etree.Element(W + "tbl")
    tbl_pr = etree.SubElement(tbl, W + "tblPr")
    tbl_w = etree.SubElement(tbl_pr, W + "tblW")
    tbl_w.set(W + "w", str(sum(widths)))
    tbl_w.set(W + "type", "dxa")
    tbl_ind = etree.SubElement(tbl_pr, W + "tblInd")
    tbl_ind.set(W + "w", "284")
    tbl_ind.set(W + "type", "dxa")
    layout = etree.SubElement(tbl_pr, W + "tblLayout")
    layout.set(W + "type", "fixed")
    borders = etree.SubElement(tbl_pr, W + "tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        border = etree.SubElement(borders, W + edge)
        border.set(W + "val", "single")
        border.set(W + "sz", "6")
        border.set(W + "space", "0")
        border.set(W + "color", "000000")
    grid = etree.SubElement(tbl, W + "tblGrid")
    for width in widths:
        etree.SubElement(grid, W + "gridCol").set(W + "w", str(width))

    def add_row(values, header=False):
        tr = etree.SubElement(tbl, W + "tr")
        tr_pr = etree.SubElement(tr, W + "trPr")
        if header:
            etree.SubElement(tr_pr, W + "tblHeader").set(W + "val", "true")
        for col_index, (value, width) in enumerate(zip(values, widths)):
            tc = etree.SubElement(tr, W + "tc")
            tc_pr = etree.SubElement(tc, W + "tcPr")
            tc_w = etree.SubElement(tc_pr, W + "tcW")
            tc_w.set(W + "w", str(width))
            tc_w.set(W + "type", "dxa")
            margins = etree.SubElement(tc_pr, W + "tcMar")
            for side, amount in (("top", "80"), ("left", "90"), ("bottom", "80"), ("right", "90")):
                margin = etree.SubElement(margins, W + side)
                margin.set(W + "w", amount)
                margin.set(W + "type", "dxa")
            etree.SubElement(tc_pr, W + "vAlign").set(W + "val", "center")
            if header:
                shd = etree.SubElement(tc_pr, W + "shd")
                shd.set(W + "val", "clear")
                shd.set(W + "fill", "D9EAF7")
            p = etree.SubElement(tc, W + "p")
            set_text(p, str(value), bold=header, size=18, justify=False)
            ppr = p.find("w:pPr", NS)
            jc = ppr.find("w:jc", NS)
            if jc is None:
                jc = etree.SubElement(ppr, W + "jc")
            jc.set(W + "val", "left" if col_index == 1 else "center")
            spacing = ppr.find("w:spacing", NS)
            spacing.set(W + "after", "0")
            spacing.set(W + "line", "220")
        return tr

    add_row(headers, header=True)
    for row in rows:
        add_row(row)
    return tbl


def main():
    with ZipFile(SOURCE, "r") as zin:
        xml = zin.read("word/document.xml")
        root = etree.fromstring(xml)
        body = root.find("w:body", NS)
        paragraphs = body.findall("w:p", NS)
        by_text = {para_text(p): p for p in paragraphs if para_text(p)}
        start1 = paragraphs.index(by_text["1. 1 Giới thiệu đề tài"])
        start2_title = paragraphs.index(by_text["PHẦN 2. KHẢO SÁT HỆ THỐNG"])
        start3 = paragraphs.index(by_text["PHẦN 3. PHÂN TÍCH  HỆ THỐNG"])

        h2_template = by_text["1. 1 Giới thiệu đề tài"]
        h3_template = by_text["1.1.1 Lý do chọn đề tài"]
        body_template = by_text["<<vì sao chọn đề tài này mà không chọn đề tài khác???>"]
        placeholder_template = by_text["<<danh sách các thành viên tham gia làm dự án, ghi rõ vai trò của từng thành viên trong dự án, ghi giống phiếu đăng ký đề tài>>"]

        def build(items):
            result = []
            for kind, text in items:
                if kind == "h2":
                    result.append(clone_as(h2_template, text, bold=True, size=32, justify=False))
                elif kind == "h3":
                    result.append(clone_as(h3_template, text, bold=False, size=28, justify=False))
                elif kind == "placeholder":
                    result.append(clone_as(placeholder_template, text, bold=False, color="0070C0", size=26))
                elif kind == "source":
                    result.append(clone_as(body_template, text, bold=False, size=22, justify=False))
                elif kind == "swot_heading":
                    result.append(clone_as(h3_template, text, bold=True, size=28, justify=False))
                elif kind == "case":
                    case_p = clone_as(body_template, text, bold=False, size=24, justify=False)
                    spacing = case_p.find("w:pPr/w:spacing", NS)
                    spacing.set(W + "after", "40")
                    spacing.set(W + "line", "300")
                    result.append(case_p)
                else:
                    result.append(clone_as(body_template, text, bold=False, size=26))
            return result

        set_text(by_text["TÊN ĐỀ TÀI"], "XE GHÉP TUYẾN F-GROUP", bold=True, size=32, justify=False)

        for p in paragraphs[start1:start2_title]:
            body.remove(p)
        insert_at = list(body).index(by_text["PHẦN 2. KHẢO SÁT HỆ THỐNG"])
        for p in build(part1):
            body.insert(insert_at, p)
            insert_at += 1

        current = body.findall("w:p", NS)
        title2 = by_text["PHẦN 2. KHẢO SÁT HỆ THỐNG"]
        start3_node = by_text["PHẦN 3. PHÂN TÍCH  HỆ THỐNG"]
        title2_ppr = title2.find("w:pPr", NS)
        page_break = title2_ppr.find("w:pageBreakBefore", NS)
        if page_break is None:
            page_break = etree.SubElement(title2_ppr, W + "pageBreakBefore")
        page_break.set(W + "val", "true")
        start3_ppr = start3_node.find("w:pPr", NS)
        page_break3 = start3_ppr.find("w:pageBreakBefore", NS)
        if page_break3 is None:
            page_break3 = etree.SubElement(start3_ppr, W + "pageBreakBefore")
        page_break3.set(W + "val", "true")
        title2_pos = list(body).index(title2)
        start3_pos = list(body).index(start3_node)
        for node in list(body)[title2_pos + 1:start3_pos]:
            if node.tag == W + "p":
                body.remove(node)
        insert_at = list(body).index(start3_node)
        for p in build(part2):
            body.insert(insert_at, p)
            insert_at += 1

        heading31 = by_text["3.1 Phân tích hiện trạng"]
        heading32 = by_text["3.2 Danh sách tác nhân (actor)"]
        pos31 = list(body).index(heading31)
        pos32 = list(body).index(heading32)
        for node in list(body)[pos31 + 1:pos32]:
            if node.tag == W + "p":
                body.remove(node)
        insert_at = list(body).index(heading32)
        for p in build(part3_1):
            body.insert(insert_at, p)
            insert_at += 1

        heading33 = by_text["3.3 Danh sách các use case"]
        heading34 = by_text["3.4 Mô hình hệ thống (Use case model)"]
        pos33 = list(body).index(heading33)
        pos34 = list(body).index(heading34)
        for node in list(body)[pos33 + 1:pos34]:
            if node.tag == W + "p":
                body.remove(node)
        insert_at = list(body).index(heading34)
        for p in build(part3_3):
            body.insert(insert_at, p)
            insert_at += 1

        usecase_table = None
        for table in body.findall("w:tbl", NS):
            table_text = " ".join(table.xpath(".//w:t/text()", namespaces=NS))
            if "Usecase" in table_text and "Mô tả chung" in table_text:
                usecase_table = table
                break
        if usecase_table is None:
            raise RuntimeError("Không tìm thấy bảng mô tả use case trong tài liệu mẫu")

        rows = usecase_table.findall("w:tr", NS)
        header_row = rows[0]
        row_template = deepcopy(rows[1])
        header_props = header_row.find("w:trPr", NS)
        if header_props is None:
            header_props = etree.Element(W + "trPr")
            header_row.insert(0, header_props)
        if header_props.find("w:tblHeader", NS) is None:
            etree.SubElement(header_props, W + "tblHeader").set(W + "val", "true")
        for row in rows[1:]:
            usecase_table.remove(row)

        for index, (code, name, description, input_text, output_text) in enumerate(usecase_descriptions, 1):
            if index in {5, 12, 19, 26, 33}:
                usecase_table.append(deepcopy(header_row))
            row = deepcopy(row_template)
            values = [str(index), f"{code} – {name}", description, input_text, output_text]
            cells = row.findall("w:tc", NS)
            for cell, value in zip(cells, values):
                paragraphs_in_cell = cell.findall("w:p", NS)
                target_p = paragraphs_in_cell[0]
                set_text(target_p, value, bold=False, size=20, justify=False)
                spacing = target_p.find("w:pPr/w:spacing", NS)
                spacing.set(W + "after", "0")
                spacing.set(W + "line", "240")
                for extra_p in paragraphs_in_cell[1:]:
                    cell.remove(extra_p)
            usecase_table.append(row)

        permission_placeholder = None
        for text_value, paragraph in by_text.items():
            if "Bảng này dùng liệt kê danh sách nhóm đối tượng người dùng" in text_value:
                permission_placeholder = paragraph
                break
        if permission_placeholder is not None:
            set_text(
                permission_placeholder,
                "Ký hiệu X thể hiện nhóm người dùng được phép sử dụng chức năng tương ứng.",
                bold=False,
                size=24,
                justify=False,
            )

        old_permission_table = None
        for table in body.findall("w:tbl", NS):
            table_text = " ".join(table.xpath(".//w:t/text()", namespaces=NS))
            if "Chức năng" in table_text and "Khách" in table_text and "Quản trị" in table_text:
                old_permission_table = table
                break
        if old_permission_table is None:
            raise RuntimeError("Không tìm thấy bảng ma trận phân quyền trong tài liệu mẫu")
        headers = ["STT", "Chức năng", "Khách vãng lai", "Khách hàng", "Tài xế", "Nhà xe", "Quản trị viên"]
        matrix_rows = []
        for index, (function_name, *permissions) in enumerate(permission_matrix, 1):
            matrix_rows.append([index, function_name, *["X" if allowed else "" for allowed in permissions]])
        new_permission_table = make_matrix_table(
            headers,
            matrix_rows,
            [550, 2600, 1100, 1100, 1100, 1100, 1100],
        )
        body.replace(old_permission_table, new_permission_table)

        settings_xml = zin.read("word/settings.xml")
        settings = etree.fromstring(settings_xml)
        update = settings.find("w:updateFields", NS)
        if update is None:
            update = etree.SubElement(settings, W + "updateFields")
        update.set(W + "val", "true")

        OUTPUT.parent.mkdir(parents=True, exist_ok=True)
        with ZipFile(OUTPUT, "w", ZIP_DEFLATED) as zout:
            for item in zin.infolist():
                if item.filename == "word/document.xml":
                    data = etree.tostring(root, xml_declaration=True, encoding="UTF-8", standalone=True)
                elif item.filename == "word/settings.xml":
                    data = etree.tostring(settings, xml_declaration=True, encoding="UTF-8", standalone=True)
                else:
                    data = zin.read(item.filename)
                zout.writestr(item, data)
    print(OUTPUT.resolve())


if __name__ == "__main__":
    main()
