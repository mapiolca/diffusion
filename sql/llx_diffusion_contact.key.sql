-- Copyright (C) 2025-2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_diffusion_contact ADD INDEX idx_diffusion_contact_rowid (rowid);
ALTER TABLE llx_diffusion_contact ADD INDEX idx_diffusion_contact_fk_diffusion (fk_diffusion);
ALTER TABLE llx_diffusion_contact ADD INDEX idx_diffusion_contact_fk_contact (fk_contact);
ALTER TABLE llx_diffusion_contact ADD INDEX idx_diffusion_contact_fk_type_contact (fk_type_contact);
ALTER TABLE llx_diffusion_contact ADD UNIQUE INDEX uk_diffusion_contact_link (fk_diffusion, fk_contact, contact_source, fk_type_contact);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_diffusion_contact ADD UNIQUE INDEX uk_diffusion_contact_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_diffusion_contact ADD CONSTRAINT llx_diffusion_contact_fk_field FOREIGN KEY (fk_field) REFERENCES llx_diffusion_myotherobject(rowid);
