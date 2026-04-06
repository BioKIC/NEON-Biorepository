<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class NeonTaxonomy extends Model implements AuthenticatableContract, AuthorizableContract{
	use Authenticatable, Authorizable, HasFactory;

	protected $table = 'neon_taxonomy';
	protected $primaryKey = 'taxonPK';
	public $timestamps = false;

	protected $fillable = [  ];

	protected $hidden = [ 'taxonPK', 'verbatimScientificName', 'tid', 'sciname', 'scientificNameAuthorship', 'family', 'acceptedTaxonCode', 'taxonProtocolCategory',
		'vernacular', 'source', 'sourceReference', 'notes', 'initialTimestamp' ];

	public function taxa() {
		return $this->belongsTo(Taxonomy::class, 'tid', 'tid');
	}
}
