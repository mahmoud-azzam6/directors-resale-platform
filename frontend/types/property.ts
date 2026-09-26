export interface CatalogItem { id: number; code: string; name_en: string; name_ar: string; category_id?: number; }
export interface OrganizationProperty { id: number; property_label: string; organization_id: number; status: string; }
export interface ProfileAggregate {
  property: OrganizationProperty;
  profile: {
    revision: number;
    property_category_id: number | null;
    unit_type_id: number | null;
    accepted_configuration_version_id?: number | null;
    geographic_location_id: number | null;
    development_reference_type: string | null;
    developer_id: number | null;
    project_id: number | null;
    project_phase_id: number | null;
  } | null;
  configuration?: CatalogItem & { version?: number; version_number?: number; status?: string } | null;
  category?: CatalogItem | null;
  unit_type?: CatalogItem | null;
  geography?: { location?: CatalogItem | null; ancestry?: CatalogItem[] } | null;
  development?: { developer?: CatalogItem | null; project?: CatalogItem | null; phase?: CatalogItem | null } | null;
  measurements: Array<{ measurement_definition_id: number; value_decimal: string; unit_code: string }>;
  attributes: Array<{ attribute_definition_id: number; data_type: string; value_integer?: number | null; value_decimal?: string | null; value_boolean?: boolean | null; value_text?: string | null; value_date?: string | null; attribute_option_id?: number | null }>;
}
export interface CorePropertyForm { categories: CatalogItem[]; unit_types: Array<CatalogItem & { category_id: number }>; geography: { countries: CatalogItem[] }; development: { developers: CatalogItem[] }; }
export interface DynamicPropertyForm { measurements: Array<{ definition_id: number; code: string; name_en: string; name_ar: string; unit: string; required: boolean }>; attributes: Array<{ definition_id: number; code: string; name_en: string; name_ar: string; data_type: 'INTEGER' | 'DECIMAL' | 'BOOLEAN' | 'TEXT' | 'ENUM' | 'DATE'; required: boolean; options: CatalogItem[] }>; }
export interface SaveProfileInput { expected_revision?: number; property_category_id?: number | null; unit_type_id?: number | null; geographic_location_id?: number | null; development_reference_type?: 'DEVELOPER' | 'PROJECT' | 'PHASE' | null; developer_id?: number | null; project_id?: number | null; project_phase_id?: number | null; measurements?: Array<{ definition_id: number; value: string; unit_code: string }>; attributes?: Array<{ definition_id: number; value?: string | number | boolean; option_id?: number }>; }
