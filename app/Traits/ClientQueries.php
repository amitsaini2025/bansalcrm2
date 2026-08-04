<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Staff;
use App\Support\StaffClientVisibility;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * ClientQueries Trait
 * 
 * Provides base query building and filtering logic for client operations.
 * Handles both admin and agent contexts automatically.
 */
trait ClientQueries
{
    /**
     * Get base client query with standard filters
     * 
     * @return Builder
     */
    protected function getBaseClientQuery(): Builder
    {
        $query = Admin::where('is_archived', '=', '0')
            ->whereNull('is_deleted');
            
        // Agent filtering - agents can only see their own clients
        if ($this->isAgentContext()) {
            $query->where('agent_id', Auth::user()->id);
        }

        return $this->applyStaffRowVisibilityIfNeeded($query);
    }
    
    /**
     * Get archived client query
     * 
     * @return Builder
     */
    protected function getArchivedClientQuery(): Builder
    {
        $query = Admin::where('is_archived', '=', '1')
            ->whereNull('is_deleted');
            
        // Agent filtering
        if ($this->isAgentContext()) {
            $query->where('agent_id', Auth::user()->id);
        }

        return $this->applyStaffRowVisibilityIfNeeded($query);
    }

    /**
     * When strict CRM allocation is on, limit listings to assigned rows or active grants.
     */
    protected function applyStaffRowVisibilityIfNeeded(Builder $query): Builder
    {
        $user = Auth::guard('admin')->user();
        if ($user instanceof Staff) {
            return StaffClientVisibility::restrictAdminsQueryForStaff($query, $user);
        }

        return $query;
    }
    
    /**
     * Apply client filters from request
     * 
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    protected function applyClientFilters(Builder $query, Request $request): Builder
    {
        // Client ID filter
        if ($request->has('client_id')) {
            $client_id = $request->input('client_id');
            if (trim($client_id) != '') {
                $query->where('client_id', '=', $client_id);
            }
        }
        
        // Type filter (admin only - agents don't have this)
        if (!$this->isAgentContext() && $request->has('type')) {
            $type = $request->input('type');
            if (trim($type) != '') {
                $query->where('type', 'ilike', $type);
            }
        }
        
        // Name filter
        if ($request->has('name')) {
            $name = $request->input('name');
            if (trim($name) != '') {
                $query->where('first_name', 'ilike', '%' . $name . '%');
            }
        }
        
        // Email filter — primary admins.email + client_emails (subquery; no join row explosion)
        if ($request->has('email')) {
            $email = $request->input('email');
            if (trim($email) != '') {
                $isAgent = $this->isAgentContext();
                $query->where(function ($q) use ($email, $isAgent) {
                    if ($isAgent) {
                        $q->where('email', $email);
                    } else {
                        $q->where('email', 'ilike', '%' . $email . '%');
                    }
                    if (Schema::hasTable('client_emails')) {
                        $q->orWhereIn('id', function ($sub) use ($email, $isAgent) {
                            $sub->select('client_id')->from('client_emails');
                            if ($isAgent) {
                                $sub->where('client_email', $email);
                            } else {
                                $sub->where('client_email', 'ilike', '%' . $email . '%');
                            }
                        });
                    }
                });
            }
        }
        
        // Phone filter — primary admins.phone + client_phones (subquery; no join row explosion)
        if ($request->has('phone')) {
            $phone = $request->input('phone');
            if (trim($phone) != '') {
                $isAgent = $this->isAgentContext();
                $query->where(function ($q) use ($phone, $isAgent) {
                    if ($isAgent) {
                        $q->where('phone', $phone);
                    } else {
                        $q->where('phone', 'ilike', '%' . $phone . '%');
                    }
                    if (Schema::hasTable('client_phones')) {
                        $q->orWhereIn('id', function ($sub) use ($phone, $isAgent) {
                            $sub->select('client_id')->from('client_phones');
                            if ($isAgent) {
                                $sub->where('client_phone', $phone);
                            } else {
                                $sub->where('client_phone', 'ilike', '%' . $phone . '%');
                            }
                        });
                    }
                });
            }
        }
        
        return $query;
    }
    
    /**
     * Apply archived-specific filters (search + archived date/archived_by/assignee).
     * Reuses applyClientFilters for name, email, phone, client_id, type.
     *
     * @param Builder $query  Archived client query (getArchivedClientQuery)
     * @param Request $request
     * @return Builder
     */
    protected function applyArchivedFilters(Builder $query, Request $request): Builder
    {
        $query = $this->applyClientFilters($query, $request);
        
        if ($request->has('archived_from') && trim($request->input('archived_from')) !== '') {
            $query->whereDate('archived_on', '>=', $request->input('archived_from'));
        }
        if ($request->has('archived_to') && trim($request->input('archived_to')) !== '') {
            $query->whereDate('archived_on', '<=', $request->input('archived_to'));
        }
        if ($request->has('archived_by') && trim($request->input('archived_by')) !== '') {
            $query->where('archived_by', '=', $request->input('archived_by'));
        }
        if ($request->has('assignee') && trim($request->input('assignee')) !== '') {
            $aid = $request->input('assignee');
            $query->where(function ($q) use ($aid) {
                $q->where('assignee', '=', $aid)
                  ->orWhere('assignee', 'like', $aid . ',%')
                  ->orWhere('assignee', 'like', '%,' . $aid . ',%')
                  ->orWhere('assignee', 'like', '%,' . $aid);
            });
        }
        
        return $query;
    }
    
    /**
     * Check if current context is agent
     * 
     * @return bool
     */
    /**
     * Check if current context is agent (deprecated - agents don't have login access)
     * 
     * @return bool Always returns false since agents don't log in
     */
    protected function isAgentContext(): bool
    {
        // Agents don't have login access - they exist only as records/accounting
        return false;
    }
    
    /**
     * Get empty client query (for users without access)
     * 
     * @return Builder
     */
    protected function getEmptyClientQuery(): Builder
    {
        return Admin::whereRaw('1 = 0');
    }
    
    /**
     * Get client by ID with context-aware filtering
     * 
     * @param int|string $id
     * @return Admin|null
     */
    protected function getClientById($id): ?Admin
    {
        $query = Admin::where('id', $id);
            
        // Agent filtering
        if ($this->isAgentContext()) {
            $query->where('agent_id', Auth::user()->id);
        }

        $user = Auth::guard('admin')->user();
        if ($user instanceof Staff) {
            StaffClientVisibility::restrictAdminsQueryForStaff($query, $user);
        }
        
        return $query->first();
    }
    
    /**
     * Get client by encoded ID with context-aware filtering
     * 
     * @param string $encodedId
     * @return Admin|null
     */
    protected function getClientByEncodedId(string $encodedId): ?Admin
    {
        $id = $this->decodeString($encodedId);
        if (!$id) {
            return null;
        }
        
        return $this->getClientById($id);
    }
}

