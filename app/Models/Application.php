<?php

namespace App\Models;

use Kyslik\ColumnSortable\Sortable;

class Application extends BaseModel
{
    use Sortable;
	
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	
	protected $fillable = [
        'id', 'client_id', 'user_id', 'product_id', 'enrolment_type', 'company_name', 'partner_id', 'branch',
        'workflow', 'stage', 'status', 'checklist_sheet_status', 'checklist_sent_at', 'created_at', 'updated_at'
    ];

    public const ENROLMENT_TYPE_TRANSFER = 'transfer_option';
    public const ENROLMENT_TYPE_COURSE_PROGRESSION = 'course_progression';

    public const COMPANY_BANSAL_EDUCATION_GROUP = 'bansal_education_group';
    public const COMPANY_ELITE_11 = 'elite_11';

    public static function enrolmentTypeOptions(): array
    {
        return [
            self::ENROLMENT_TYPE_TRANSFER => 'Transfer',
            self::ENROLMENT_TYPE_COURSE_PROGRESSION => 'Course progression',
        ];
    }

    public static function companyNameOptions(): array
    {
        return [
            self::COMPANY_BANSAL_EDUCATION_GROUP => 'Bansal Education Group',
            self::COMPANY_ELITE_11 => 'Elite 11',
        ];
    }

    /**
     * Update Enrolment Type / Company Name after create: Super Admin (1) and Admin (12) only.
     */
    public static function canUpdateEnrolmentAndCompanyFields(?object $user = null): bool
    {
        $user = $user ?? \Auth::guard('admin')->user();

        return $user && in_array((int) ($user->role ?? 0), [1, 12], true);
    }

    /**
     * Super Admin/Admin can always edit.
     * Other roles may only set a value when the current stored value is empty (first fill).
     */
    public static function canEditEnrolmentOrCompanyValue(?string $currentValue, ?object $user = null): bool
    {
        if (self::canUpdateEnrolmentAndCompanyFields($user)) {
            return true;
        }

        return $currentValue === null || $currentValue === '';
    }

    public static function companyNameLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::companyNameOptions()[$value] ?? $value;
    }

    public static function normalizeCompanyName(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $options = self::companyNameOptions();
        if (array_key_exists($value, $options)) {
            return $value;
        }

        $normalized = strtolower(trim($value));
        foreach ($options as $key => $label) {
            if ($normalized === strtolower($key) || $normalized === strtolower($label)) {
                return $key;
            }
        }

        return $value;
    }

    public static function companyNameSelectHtml(int $applicationId, ?string $currentValue, string $cssClass = 'form-control form-control-sm company-name-field', bool $disabled = false): string
    {
        $currentValue = self::normalizeCompanyName($currentValue);
        $disabledAttr = $disabled ? ' disabled="disabled"' : '';
        $html = '<select class="'.e($cssClass).'" data-application-id="'.(int) $applicationId.'" data-company-name="'.e($currentValue).'"'.$disabledAttr.'>';
        $html .= '<option value=""'.($currentValue === '' ? ' selected="selected"' : '').'>Select</option>';

        foreach (self::companyNameOptions() as $value => $label) {
            $selected = $currentValue === $value ? ' selected="selected"' : '';
            $html .= '<option value="'.e($value).'"'.$selected.'>'.e($label).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public static function enrolmentTypeLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::enrolmentTypeOptions()[$value] ?? $value;
    }

    public static function normalizeEnrolmentType(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $options = self::enrolmentTypeOptions();
        if (array_key_exists($value, $options)) {
            return $value;
        }

        $normalized = strtolower(trim($value));
        foreach ($options as $key => $label) {
            if ($normalized === strtolower($key) || $normalized === strtolower($label)) {
                return $key;
            }
        }

        return $value;
    }

    public static function enrolmentTypeSelectHtml(int $applicationId, ?string $currentValue, string $cssClass = 'form-control form-control-sm enrolment-type-field', bool $disabled = false): string
    {
        $currentValue = self::normalizeEnrolmentType($currentValue);
        $disabledAttr = $disabled ? ' disabled="disabled"' : '';
        $html = '<select class="'.e($cssClass).'" data-application-id="'.(int) $applicationId.'" data-enrolment-type="'.e($currentValue).'"'.$disabledAttr.'>';
        $html .= '<option value=""'.($currentValue === '' ? ' selected="selected"' : '').'>Select</option>';

        foreach (self::enrolmentTypeOptions() as $value => $label) {
            $selected = $currentValue === $value ? ' selected="selected"' : '';
            $html .= '<option value="'.e($value).'"'.$selected.'>'.e($label).'</option>';
        }

        $html .= '</select>';

        return $html;
    }
	
	public $sortable = ['id', 'created_at', 'updated_at'];
    
    public function application_assignee()
    {
        return $this->belongsTo('App\Models\Staff', 'user_id', 'id');
    }
	
	public function client()
	{
		return $this->belongsTo(Admin::class, 'client_id', 'id');
	}

	public function product()
	{
		return $this->belongsTo('App\Models\Product', 'product_id', 'id');
	}
	
	public function partner()
	{
		return $this->belongsTo('App\Models\Partner', 'partner_id', 'id');
	}
	
	public function branch()
	{
		return $this->belongsTo('App\Models\PartnerBranch', 'branch', 'id');
	}
	
	public function workflow()
	{
		return $this->belongsTo('App\Models\Workflow', 'workflow', 'id');
	}
	
	public function invoices()
	{
		return $this->hasMany('App\Models\Invoice', 'application_id', 'id');
	}
	
}

