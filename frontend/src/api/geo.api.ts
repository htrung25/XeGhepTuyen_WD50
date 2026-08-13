import { index as provincesIndex } from '@/actions/App/Http/Controllers/Public/ProvinceController';
import { apiClient } from './client';

export interface District {
    code: string;
    name: string;
}

export interface Province {
    code: string;
    name: string;
    districts: District[];
}

// Danh mục hành chính là dữ liệu tĩnh (~33KB): chỉ gọi API một lần cho cả
// phiên, các lần gọi song song dùng chung một promise thay vì bắn nhiều request.
let cache: Province[] | null = null;
let inflight: Promise<Province[]> | null = null;

export const geoApi = {
    async getProvinces(): Promise<Province[]> {
        if (cache) return cache;
        if (inflight) return inflight;

        inflight = apiClient
            .send(provincesIndex())
            .then(({ data }: { data: Province[] | null }) => {
                cache = data ?? [];
                inflight = null;
                return cache;
            })
            .catch(() => {
                inflight = null;
                return [];
            });

        return inflight;
    },

    /**
     * Tra ngược mã từ tên đã lưu trong DB (routes chỉ lưu tên tỉnh/huyện) để
     * đổ ngược lên dropdown khi sửa tuyến.
     */
    async resolveCodes(
        provinceName?: string | null,
        districtName?: string | null,
    ): Promise<{ provinceCode: string; districtCode: string }> {
        const list = await this.getProvinces();
        const province = list.find((p) => p.name === provinceName);
        const district = province?.districts.find(
            (d) => d.name === districtName,
        );

        return {
            provinceCode: province?.code ?? '',
            districtCode: district?.code ?? '',
        };
    },

    /** Chỉ dùng cho test */
    clearCache() {
        cache = null;
        inflight = null;
    },
};
